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
 * Category modal management for Local Custom Admin plugin
 *
 * @package   local_localcustomadmin
 * @copyright 2025 Heber
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal'], function($, Modal) {
    
    var CategoryModal = {
        
        /**
         * Initialize the category modal functionality
         */
        init: function() {
            this.bindEvents();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Add Category button handler
            $(document).on('click', '#add-category-btn', function(e) {
                e.preventDefault();
                var url = $(this).data('url');
                self.showCategoryModal(url, 'Add New Category');
            });
            
            // Edit Category button handler
            $(document).on('click', '.edit-category-modal', function(e) {
                e.preventDefault();
                var url = $(this).data('url');
                var categoryName = $(this).data('category-name');
                self.showCategoryModal(url, 'Edit Category: ' + categoryName);
            });
        },
        
        /**
         * Show category modal
         * @param {string} url - URL to load form
         * @param {string} title - Modal title
         */
        showCategoryModal: function(url, title) {
            var self = this;
            
            Modal.create({
                title: title,
                body: this.getLoadingBody(),
                large: true,
                show: true
            }).then(function(modal) {
                
                // Load content via AJAX
                var modalUrl = url + (url.indexOf('?') > -1 ? '&modal=1' : '?modal=1');
                $.get(modalUrl)
                    .done(function(response) {
                        modal.setBody(response);
                        self.initFormHandlers(modal);
                    })
                    .fail(function() {
                        modal.setBody('<div class="alert alert-danger">Error loading form. Please try again.</div>');
                    });
                
                return modal;
                
            }).catch(function(error) {
                console.error('Error creating modal:', error);
                alert('Erro ao criar modal: ' + error.message);
            });
        },
        
        /**
         * Initialize form handlers within modal
         * @param {Object} modal - Modal object
         */
        initFormHandlers: function(modal) {
            var self = this;
            
            // Handle form submission directly
            var modalBody = modal.getBody();
            modalBody.on('submit', 'form', function(e) {
                e.preventDefault();
                var form = $(this);
                
                var submitBtn = form.find('input[type="submit"]');
                var originalText = submitBtn.val();
                submitBtn.val('Saving...').prop('disabled', true);
                
                $.ajax({
                    url: form.attr('action') || window.location.href,
                    method: 'POST',
                    data: new FormData(form[0]),
                    processData: false,
                    contentType: false,
                    success: function(formResponse) {
                        if (formResponse.indexOf('alert-success') !== -1) {
                            modal.hide();
                            setTimeout(function() {
                                window.location.reload();
                            }, 500);
                        } else {
                            modal.setBody(formResponse);
                            self.initFormHandlers(modal);
                        }
                    },
                    error: function() {
                        modal.setBody('<div class="alert alert-danger">Error saving category. Please try again.</div>');
                    },
                    complete: function() {
                        submitBtn.val(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Handle cancel button
            modalBody.on('click', 'input[name="cancel"]', function(e) {
                e.preventDefault();
                modal.hide();
            });
        },
        
        /**
         * Get loading body content
         * @return {string} HTML content
         */
        getLoadingBody: function() {
            return '<div class="text-center p-4">' +
                   '<i class="fa fa-spinner fa-spin fa-2x text-primary"></i><br>' +
                   '<div class="mt-2">Loading form...</div>' +
                   '</div>';
        }
    };
    
    return CategoryModal;
});