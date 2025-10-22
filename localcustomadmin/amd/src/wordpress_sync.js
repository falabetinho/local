// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * WordPress Sync Module
 *
 * @module     local_localcustomadmin/wordpress_sync
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    var WordPressSync = {

        /**
         * Initialize the module
         */
        init: function() {
            console.log('WordPress Sync module initialized');
            this.attachEventListeners();
        },

        /**
         * Attach event listeners
         */
        attachEventListeners: function() {
            var self = this;
            console.log('Attaching event listeners for WordPress sync');

            // Handle action buttons
            $(document).on('click', '[data-action]', function(e) {
                e.preventDefault();
                var action = $(this).data('action');
                var $button = $(this);
                
                console.log('Action clicked:', action);

                switch(action) {
                    case 'sync-categories':
                        self.syncCategories($button);
                        break;
                    case 'test-connection':
                        self.testConnection($button);
                        break;
                    case 'sync-single-category':
                        var categoryId = $(this).data('categoryid');
                        self.syncSingleCategory(categoryId, $button);
                        break;
                }
            });
        },

        /**
         * Sync all categories
         */
        syncCategories: function($button) {
            var self = this;
            
            console.log('syncCategories method called');

            // Disable button and show loading
            $button.prop('disabled', true);
            var originalHtml = $button.html();
            $button.html('<i class="fa fa-spinner fa-spin"></i> Sincronizando...');
            
            console.log('Making AJAX request to sync categories');

            // Make AJAX request
            $.ajax({
                url: M.cfg.wwwroot + '/local/localcustomadmin/ajax/sync_categories.php?action=sync_all&sesskey=' + M.cfg.sesskey,
                method: 'POST',
                dataType: 'json',
                success: function(response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        var data = response.data;
                        var message = 'Sincronização concluída!\n';
                        message += 'Criadas: ' + data.success + '\n';
                        message += 'Atualizadas: ' + data.updated + '\n';
                        message += 'Erros: ' + data.errors + '\n';
                        message += 'Ignoradas: ' + data.skipped;

                        Notification.addNotification({
                            message: message,
                            type: 'success'
                        });

                        // Update statistics without reloading
                        self.updateStatistics();
                    } else {
                        Notification.addNotification({
                            message: 'Erro na sincronização: ' + response.error,
                            type: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    console.error('Response:', xhr.responseText);
                    Notification.addNotification({
                        message: 'Erro ao conectar com o servidor: ' + error,
                        type: 'error'
                    });
                },
                complete: function() {
                    // Re-enable button
                    $button.prop('disabled', false);
                    $button.html(originalHtml);
                }
            });
        },

        /**
         * Sync single category
         */
        syncSingleCategory: function(categoryId, $button) {
            // Disable button and show loading
            $button.prop('disabled', true);
            var originalHtml = $button.html();
            $button.html('<i class="fa fa-spinner fa-spin"></i>');

            // Make AJAX request
            $.ajax({
                url: M.cfg.wwwroot + '/local/localcustomadmin/ajax/sync_categories.php',
                method: 'POST',
                data: {
                    action: 'sync_single',
                    categoryid: categoryId,
                    sesskey: M.cfg.sesskey
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Notification.addNotification({
                            message: 'Categoria sincronizada com sucesso!',
                            type: 'success'
                        });

                        // Update statistics without reloading
                        self.updateStatistics();
                    } else {
                        Notification.addNotification({
                            message: 'Erro: ' + response.error,
                            type: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Notification.addNotification({
                        message: 'Erro ao conectar: ' + error,
                        type: 'error'
                    });
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.html(originalHtml);
                }
            });
        },

        /**
         * Test WordPress connection
         */
        testConnection: function($button) {
            console.log('testConnection method called');
            
            // Disable button and show loading
            $button.prop('disabled', true);
            var originalHtml = $button.html();
            $button.html('<i class="fa fa-spinner fa-spin"></i> Testando...');
            
            console.log('Making AJAX request to test connection');

            // Make AJAX request
            $.ajax({
                url: M.cfg.wwwroot + '/local/localcustomadmin/ajax/test_connection.php',
                method: 'POST',
                data: {
                    sesskey: M.cfg.sesskey
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Test connection response:', response);
                    if (response.success && response.connected) {
                        Notification.addNotification({
                            message: '✓ Conexão com WordPress OK!\nEndpoint: ' + response.endpoint,
                            type: 'success'
                        });
                    } else {
                        Notification.addNotification({
                            message: '✗ Falha na conexão\n' + (response.error || response.message),
                            type: 'error'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Test connection error:', status, error);
                    console.error('Response:', xhr.responseText);
                    Notification.addNotification({
                        message: 'Erro ao testar conexão: ' + error,
                        type: 'error'
                    });
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.html(originalHtml);
                }
            });
        },

        /**
         * Update statistics dynamically
         */
        updateStatistics: function() {
            console.log('Updating statistics...');
            
            $.ajax({
                url: M.cfg.wwwroot + '/local/localcustomadmin/ajax/sync_categories.php?action=get_stats&sesskey=' + M.cfg.sesskey,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        var stats = response.data;
                        
                        // Update statistics cards
                        $('[data-stat="total"]').text(stats.total || 0);
                        $('[data-stat="synced"]').text(stats.synced || 0);
                        $('[data-stat="pending"]').text(stats.pending || 0);
                        
                        console.log('Statistics updated:', stats);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating statistics:', error);
                }
            });
        }
    };

    return WordPressSync;
});
