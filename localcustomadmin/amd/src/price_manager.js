/**
 * Price Manager AMD Module for Local Custom Admin
 * 
 * Handles CRUD operations for category prices via AJAX
 * 
 * @package   local_localcustomadmin
 * @copyright 2025 Heber
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/modal', 'core/modal_factory'], function($, notification, Modal, ModalFactory) {
    return {
        /**
         * Get Bootstrap instance (workaround for AMD scope)
         */
        getBootstrap: function() {
            return window.bootstrap || null;
        },

        /**
         * Initialize the price manager
         * 
         * @param {number} categoryId - The category ID
         */
        init: function(categoryId) {
            this.categoryId = categoryId;
            this.setupEventHandlers();
            this.loadPrices();
        },

        /**
         * Setup event handlers for price management UI
         */
        setupEventHandlers: function() {
            var self = this;

            // Add price button
            $(document).on('click', '#btn-add-price', function() {
                self.showPriceForm(null);
            });

            // Save price button
            $(document).on('click', '#btn-save-price', function() {
                self.savePrice();
            });

            // Edit price
            $(document).on('click', '.btn-edit-price', function(e) {
                e.preventDefault();
                var priceId = $(this).data('price-id');
                self.editPrice(priceId);
            });

            // Delete price
            $(document).on('click', '.btn-delete-price', function(e) {
                e.preventDefault();
                var priceId = $(this).data('price-id');
                self.deletePrice(priceId);
            });
        },

        /**
         * Load prices from database
         */
        loadPrices: function() {
            var self = this;

            $.ajax({
                type: 'POST',
                url: M.cfg.wwwroot + '/local/localcustomadmin/ajax/price_action.php',
                dataType: 'json',
                data: {
                    action: 'getprices',
                    categoryid: self.categoryId,
                    activeonly: true
                },
                success: function(response) {
                    console.log('Price response:', response);
                    
                    if (response.success && response.data && Array.isArray(response.data)) {
                        self.renderPricesTable(response.data);
                    } else {
                        console.warn('Invalid price data received:', response.data);
                        self.renderPricesTable([]);
                    }
                },
                error: function(xhr, status, error) {
                    // Silently handle error - just display empty table
                    console.log('No prices loaded or service error:', error);
                    self.renderPricesTable([]);
                }
            });
        },

        /**
         * Render prices table
         * 
         * @param {array} prices - Array of price objects
         */
        renderPricesTable: function(prices) {
            var tbody = $('#prices-tbody');
            tbody.html('');

            // Ensure prices is an array
            if (!Array.isArray(prices) || prices.length === 0) {
                tbody.html('<tr><td colspan="9" class="text-center text-muted">No prices found. Click "Add Price" to create one.</td></tr>');
                return;
            }

            prices.forEach(function(price) {
                var row = '<tr>';
                row += '<td><strong>' + (price.name || '-') + '</strong></td>';
                row += '<td>R$ ' + parseFloat(price.price).toFixed(2) + '</td>';
                row += '<td>' + new Date(price.startdate * 1000).toLocaleString() + '</td>';
                row += '<td>' + (price.enddate ? new Date(price.enddate * 1000).toLocaleString() : '-') + '</td>';
                row += '<td><span class="badge ' + (price.ispromotional == 1 ? 'bg-warning' : 'bg-secondary') + '">' + 
                    (price.ispromotional == 1 ? 'Yes' : 'No') + '</span></td>';
                row += '<td><span class="badge ' + (price.isenrollmentfee == 1 ? 'bg-info' : 'bg-secondary') + '">' + 
                    (price.isenrollmentfee == 1 ? 'Yes' : 'No') + '</span></td>';
                row += '<td>' + price.installments + '</td>';
                row += '<td><span class="badge ' + (price.status == 1 ? 'bg-success' : 'bg-danger') + '">' + 
                    (price.status == 1 ? 'Active' : 'Inactive') + '</span></td>';
                row += '<td>';
                row += '<button type="button" class="btn btn-sm btn-warning me-2 btn-edit-price" data-price-id="' + price.id + '">' +
                    '<i class="fas fa-edit"></i></button>';
                row += '<button type="button" class="btn btn-sm btn-danger btn-delete-price" data-price-id="' + price.id + '">' +
                    '<i class="fas fa-trash"></i></button>';
                row += '</td>';
                row += '</tr>';
                tbody.append(row);
            });
        },

        /**
         * Show price form (add or edit)
         * 
         * @param {object|null} priceData - Price data if editing, null if adding
         */
        showPriceForm: function(priceData) {
            var self = this;
            var title = priceData ? 'Edit Price' : 'Add Price';
            
            // Clear any previous error alerts
            self.hideErrorAlert();
            
            $('#priceModalLabel').text(title);
            
            if (priceData) {
                // Edit mode: populate form with existing data
                $('#price_id').val(priceData.id);
                $('#price-name').val(priceData.name || '');
                $('#price-value').val(priceData.price);
                $('#validity-start').val(new Date(priceData.startdate * 1000).toISOString().slice(0, 16));
                $('#validity-end').val(priceData.enddate ? new Date(priceData.enddate * 1000).toISOString().slice(0, 16) : '');
                $('#is-promotional').prop('checked', priceData.ispromotional == 1);
                $('#is-enrollment-fee').prop('checked', priceData.isenrollmentfee == 1);
                $('#scheduled-task').prop('checked', priceData.scheduledtask == 1);
                $('#installments').val(priceData.installments || 0);
                $('#price-status').val(priceData.status);
            } else {
                // Add mode: reset and initialize with default dates
                self.resetPriceForm();
                self.initializeDateFields();
            }

            var bootstrap = self.getBootstrap();
            if (bootstrap) {
                var priceModal = new bootstrap.Modal(document.getElementById('priceModal'));
                priceModal.show();
            }
        },

        /**
         * Initialize date fields with default values
         * Start date: today at 00:00
         * End date: today + 5 years at 00:00
         */
        initializeDateFields: function() {
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Start date: today
            var startDateStr = this.formatDateForInput(today);
            $('#validity-start').val(startDateStr);
            
            // End date: today + 5 years
            var endDate = new Date(today);
            endDate.setFullYear(endDate.getFullYear() + 5);
            var endDateStr = this.formatDateForInput(endDate);
            $('#validity-end').val(endDateStr);
        },

        /**
         * Format date for datetime-local input
         * Format: YYYY-MM-DDTHH:mm
         * 
         * @param {Date} date - The date to format
         * @returns {string} Formatted date string
         */
        formatDateForInput: function(date) {
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, '0');
            var day = String(date.getDate()).padStart(2, '0');
            var hours = String(date.getHours()).padStart(2, '0');
            var minutes = String(date.getMinutes()).padStart(2, '0');
            
            return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
        },

        /**
         * Edit price
         * 
         * @param {number} priceId - Price ID to edit
         */
        editPrice: function(priceId) {
            var self = this;

            $.ajax({
                type: 'POST',
                url: M.cfg.wwwroot + '/local/localcustomadmin/ajax/price_action.php',
                dataType: 'json',
                data: {
                    action: 'getprices',
                    categoryid: self.categoryId,
                    activeonly: false
                },
                success: function(response) {
                    if (response.success && Array.isArray(response.data) && response.data.length > 0) {
                        var price = response.data.find(function(p) { return p.id == priceId; });
                        if (price) {
                            self.showPriceForm(price);
                        }
                    }
                },
                error: function() {
                    self.showErrorAlert('Failed to load price details');
                }
            });
        },

        /**
         * Save price (create or update)
         */
        savePrice: function() {
            var self = this;
            var priceId = $('#price_id').val();
            var priceName = $('#price-name').val();
            var price = $('#price-value').val();
            var validityStart = new Date($('#validity-start').val()).getTime() / 1000;
            var validityEnd = $('#validity-end').val() ? new Date($('#validity-end').val()).getTime() / 1000 : null;
            var isPromotional = $('#is-promotional').is(':checked') ? 1 : 0;
            var isEnrollmentFee = $('#is-enrollment-fee').is(':checked') ? 1 : 0;
            var scheduledTask = $('#scheduled-task').is(':checked') ? 1 : 0;
            var installments = parseInt($('#installments').val()) || 0;
            var status = $('#price-status').val();

            // Validation
            if (!priceName || priceName.trim() === '') {
                notification.alert('Please enter a price name', 'Validation Error');
                return;
            }

            if (!price || price <= 0) {
                notification.alert('Please enter a valid price', 'Validation Error');
                return;
            }

            if (!validityStart || isNaN(validityStart)) {
                notification.alert('Please enter a valid start date', 'Validation Error');
                return;
            }

            if (installments < 0) {
                notification.alert('Installments cannot be negative', 'Validation Error');
                return;
            }

            var wsFunction = priceId ? 'updateprice' : 'createprice';
            var postData = {
                action: wsFunction,
                categoryid: self.categoryId,
                name: priceName,
                price: price,
                startdate: validityStart,
                enddate: validityEnd || null,
                ispromotional: isPromotional,
                isenrollmentfee: isEnrollmentFee,
                scheduledtask: scheduledTask,
                installments: installments,
                status: status
            };

            if (priceId) {
                postData.id = priceId;
            }

            $.ajax({
                type: 'POST',
                url: M.cfg.wwwroot + '/local/localcustomadmin/ajax/price_action.php',
                dataType: 'json',
                data: postData,
                success: function(response) {
                    if (response.success) {
                        var bootstrap = self.getBootstrap();
                        if (bootstrap) {
                            var priceModal = bootstrap.Modal.getInstance(document.getElementById('priceModal'));
                            if (priceModal) {
                                priceModal.hide();
                            }
                        }
                        self.loadPrices();
                        notification.alert(priceId ? 'Price updated successfully' : 'Price created successfully', 'Success');
                    } else {
                        self.showErrorAlert(response.error || 'Error saving price');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error saving price:', error);
                    var errorMsg = 'Failed to save price';
                    try {
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                    } catch (e) {
                        // Ignore parsing errors
                    }
                    self.showErrorAlert(errorMsg);
                }
            });
        },

        /**
         * Show error alert inside the modal
         */
        showErrorAlert: function(message) {
            var alertEl = $('#price-alert');
            var messageEl = $('#price-alert-message');
            
            messageEl.text(message);
            alertEl.removeClass('alert-success alert-info').addClass('alert-danger');
            alertEl.show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                alertEl.fadeOut();
            }, 5000);
        },

        /**
         * Hide alert inside the modal
         */
        hideErrorAlert: function() {
            $('#price-alert').hide().removeClass('alert-danger alert-success alert-info');
        },

        /**
         * Delete price
         * 
         * @param {number} priceId - Price ID to delete
         */
        deletePrice: function(priceId) {
            var self = this;

            if (!confirm('Are you sure you want to delete this price?')) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: M.cfg.wwwroot + '/local/localcustomadmin/ajax/price_action.php',
                dataType: 'json',
                data: {
                    action: 'deleteprice',
                    id: priceId
                },
                success: function(response) {
                    if (response.success) {
                        self.loadPrices();
                        notification.alert('Price deleted successfully', 'Success');
                    } else {
                        notification.alert(response.error || 'Error deleting price', 'Error');
                    }
                },
                error: function() {
                    notification.exception(new Error('Failed to delete price'));
                }
            });
        },

        /**
         * Reset price form
         */
        resetPriceForm: function() {
            $('#price-form')[0].reset();
            $('#price_id').val('');
        }
    };
});
