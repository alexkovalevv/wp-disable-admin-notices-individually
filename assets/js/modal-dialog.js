/**
 * Custom Modal Dialog for Unnotifier
 * Custom confirmation dialogs used across the plugin
 * 
 * @package Unnotifier
 * @since 1.2.2
 */

(function($) {
    'use strict';

    /**
     * UnnoModal - helper for building custom modal dialogs
     */
    window.UnnoModal = {
        /**
         * Display a confirmation modal dialog
         *
         * @param {Object} options Modal configuration options
         * @returns {Promise} Promise resolved on confirm, rejected on cancel
         */
        confirm: function(options) {
            const defaults = {
                title: 'Confirm Action',
                message: 'Are you sure?',
                confirmText: 'Confirm',
                cancelText: 'Cancel',
                type: 'warning', // warning, danger, info
                icon: 'dashicons-warning',
                confirmClass: 'unno-modal-btn-warning'
            };

            const settings = $.extend({}, defaults, options);

            return new Promise(function(resolve, reject) {
                // Build modal markup
                const modalHTML = `
                    <div class="unno-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="unno-modal-title">
                        <div class="unno-modal">
                            <button class="unno-modal-close" aria-label="Close">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                            <div class="unno-modal-header unno-modal-${settings.type}">
                                <span class="dashicons ${settings.icon}"></span>
                                <h2 class="unno-modal-title" id="unno-modal-title">${settings.title}</h2>
                            </div>
                            <div class="unno-modal-body">
                                <p>${settings.message}</p>
                            </div>
                            <div class="unno-modal-footer">
                                <button class="unno-modal-btn unno-modal-btn-secondary unno-modal-cancel">
                                    ${settings.cancelText}
                                </button>
                                <button class="unno-modal-btn ${settings.confirmClass} unno-modal-confirm">
                                    ${settings.confirmText}
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                // Append modal to the DOM
                const $modal = $(modalHTML).appendTo('body');

                // Bind modal event handlers
                $modal.find('.unno-modal-confirm').on('click', function() {
                    UnnoModal.close($modal);
                    resolve(true);
                });

                $modal.find('.unno-modal-cancel, .unno-modal-close').on('click', function() {
                    UnnoModal.close($modal);
                    reject(false);
                });

                // Close when clicking outside the dialog
                $modal.on('click', function(e) {
                    if ($(e.target).hasClass('unno-modal-overlay')) {
                        UnnoModal.close($modal);
                        reject(false);
                    }
                });

                // Close when the Escape key is pressed
                $(document).on('keydown.unnomodal', function(e) {
                    if (e.key === 'Escape') {
                        UnnoModal.close($modal);
                        reject(false);
                    }
                });

                // Focus trap
                $modal.find('.unno-modal-confirm').focus();
            });
        },

        /**
         * Display an informational alert modal
         */
        alert: function(options) {
            const defaults = {
                title: 'Notice',
                message: '',
                confirmText: 'OK',
                type: 'info',
                icon: 'dashicons-info',
                confirmClass: 'unno-modal-btn-primary'
            };

            const settings = $.extend({}, defaults, options);

            return new Promise(function(resolve) {
                const modalHTML = `
                    <div class="unno-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="unno-modal-title">
                        <div class="unno-modal">
                            <button class="unno-modal-close" aria-label="Close">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                            <div class="unno-modal-header unno-modal-${settings.type}">
                                <span class="dashicons ${settings.icon}"></span>
                                <h2 class="unno-modal-title" id="unno-modal-title">${settings.title}</h2>
                            </div>
                            <div class="unno-modal-body">
                                <p>${settings.message}</p>
                            </div>
                            <div class="unno-modal-footer">
                                <button class="unno-modal-btn ${settings.confirmClass} unno-modal-confirm">
                                    ${settings.confirmText}
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                const $modal = $(modalHTML).appendTo('body');

                $modal.find('.unno-modal-confirm, .unno-modal-close').on('click', function() {
                    UnnoModal.close($modal);
                    resolve(true);
                });

                $modal.on('click', function(e) {
                    if ($(e.target).hasClass('unno-modal-overlay')) {
                        UnnoModal.close($modal);
                        resolve(true);
                    }
                });

                $(document).on('keydown.unnomodal', function(e) {
                    if (e.key === 'Escape') {
                        UnnoModal.close($modal);
                        resolve(true);
                    }
                });

                $modal.find('.unno-modal-confirm').focus();
            });
        },

        /**
         * Close and remove a modal instance
         */
        close: function($modal) {
            $(document).off('keydown.unnomodal');
            $modal.fadeOut(200, function() {
                $modal.remove();
            });
        }
    };

})(jQuery);

