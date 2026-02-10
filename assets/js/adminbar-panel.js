/**
 * Admin Bar Notifications Panel Scripts
 * Handles the hidden notices dropdown in the WordPress admin bar
 *
 * @package Unnotifier
 * @since 1.2.2
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        /**
         * Restore notice from admin bar panel
         */
        $(document).on('click', '.unno-panel-restore-link', function(e) {
            e.preventDefault();

            var $link = $(this);
            var $listItem = $link.closest('li');
            var noticeId = $link.data('notice-id');
            var scope = $link.data('scope');
            var nonce = $link.data('nonce');
            var $counter = $('.unno-adminbar-counter');

            // Validation
            if (!noticeId) {
                console.error('Unnotifier: Notice ID is missing');
                alert(unno_adminbar.error_text || 'Error: Notice ID is missing');
                return false;
            }

            // Add loading state
            $listItem.addClass('unno-restoring');
            $link.text(unno_adminbar.restore_text + '...');

            // AJAX request to restore notice
            $.ajax({
                url: unno_adminbar.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'unno_restore_single_notice',
                    nonce: nonce,
                    notice_id: noticeId,
                    action_type: scope === 'global' ? 'all' : 'user'
                },
                success: function(response) {
                    if (response && response.success) {
                        // Update counter
                        var currentCount = parseInt($counter.text()) || 0;
                        var newCount = Math.max(0, currentCount - 1);
                        $counter.text(newCount);

                        // Remove the notice item with animation
                        $listItem.slideUp(300, function() {
                            $listItem.remove();

                            // If no notices left, hide the panel or show empty message
                            if (newCount === 0) {
                                // Optionally reload the page or remove the panel entirely
                                $('#wp-admin-bar-unno-hidden-notices-panel').fadeOut(300, function() {
                                    $(this).remove();
                                });
                            } else {
                                // Check if the group is now empty and remove group title
                                checkAndRemoveEmptyGroups();
                            }
                        });

                        // Show success message (optional)
                        if (typeof response.data !== 'undefined' && response.data.message) {
                            console.log('Unnotifier: ' + response.data.message);
                        }
                    } else {
                        // Error handling
                        $listItem.removeClass('unno-restoring');
                        $link.text(unno_adminbar.restore_text);

                        var errorMsg = unno_adminbar.error_text;
                        if (response && response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }

                        console.error('Unnotifier Error:', errorMsg);
                        alert(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    // AJAX error handling
                    $listItem.removeClass('unno-restoring');
                    $link.text(unno_adminbar.restore_text);

                    console.error('Unnotifier AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });

                    alert(unno_adminbar.error_text || 'AJAX Error occurred. Please try again.');
                }
            });

            return false;
        });

        /**
         * Check and remove empty group titles
         */
        function checkAndRemoveEmptyGroups() {
            // Check user group
            var $userGroup = $('#wp-admin-bar-unno-hidden-notices-user-group');
            if ($userGroup.length) {
                var hasUserNotices = $userGroup.nextUntil('.unno-group-title, #wp-admin-bar-unno-hidden-notices-global-group').filter('[id^="wp-admin-bar-unno-hidden-notice-"]').length > 0;
                if (!hasUserNotices) {
                    $userGroup.remove();
                }
            }

            // Check global group
            var $globalGroup = $('#wp-admin-bar-unno-hidden-notices-global-group');
            if ($globalGroup.length) {
                var hasGlobalNotices = $globalGroup.nextUntil('.unno-group-title').filter('[id^="wp-admin-bar-unno-hidden-notice-"]').length > 0;
                if (!hasGlobalNotices) {
                    $globalGroup.remove();
                }
            }
        }

        /**
         * Reset all hidden notices from admin bar
         */
        $(document).on('click', '.unno-reset-all-link', function(e) {
            e.preventDefault();

            var $link = $(this);
            var nonce = $link.data('nonce');
            var $counter = $('.unno-adminbar-counter');

            // Prefer the custom modal dialog when available, otherwise fall back to the native confirm dialog
            var confirmPromise;
            
            if (typeof UnnoModal !== 'undefined') {
                confirmPromise = UnnoModal.confirm({
                    title: unno_adminbar.reset_title || 'Reset All Hidden Notices',
                    message: unno_adminbar.reset_confirm || 'Are you sure you want to restore all hidden notices? This will make them visible again.',
                    confirmText: unno_adminbar.reset_button || 'Yes, Reset All',
                    cancelText: unno_adminbar.cancel_button || 'Cancel',
                    type: 'warning',
                    icon: 'dashicons-warning',
                    confirmClass: 'unno-modal-btn-warning'
                });
            } else {
                // Native confirm fallback when the custom modal is not loaded
                confirmPromise = new Promise(function(resolve, reject) {
                    if (confirm(unno_adminbar.reset_confirm || 'Are you sure you want to restore all hidden notices?')) {
                        resolve(true);
                    } else {
                        reject(false);
                    }
                });
            }

            confirmPromise.then(function() {
                // Add loading state
                $link.addClass('unno-resetting');
                var originalText = $link.html();
                
                // Replace the static icon with a dedicated loading spinner
                $link.html('<span class="unno-spinner"></span> ' + (unno_adminbar.resetting_text || 'Resetting...'));

            // AJAX request to reset all notices
            $.ajax({
                url: unno_adminbar.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'unno_reset_notices',
                    nonce: nonce,
                    action_type: 'all'
                },
                success: function(response) {
                    if (response && response.success) {
                        // Update counter to 0
                        $counter.text('0');

                        // Hide the entire panel
                        $('#wp-admin-bar-unno-hidden-notices-panel').fadeOut(300, function() {
                            $(this).remove();
                        });

                        // Show success message (optional)
                        if (typeof response.data !== 'undefined' && response.data.message) {
                            console.log('Unnotifier: ' + response.data.message);
                        }

                        // Reload page after a short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    } else {
                        // Error handling
                        $link.removeClass('unno-resetting');
                        $link.html(originalText);

                        var errorMsg = unno_adminbar.error_text;
                        if (response && response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }

                        console.error('Unnotifier Error:', errorMsg);
                        alert(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    // AJAX error handling
                    $link.removeClass('unno-resetting');
                    $link.html(originalText);

                    console.error('Unnotifier AJAX Error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });

                    alert(unno_adminbar.error_text || 'AJAX Error occurred. Please try again.');
                }
            });
            }).catch(function() {
                // User dismissed the confirmation prompt
                console.log('Unnotifier: Reset cancelled by user');
            });

            return false;
        });

        /**
         * Prevent default action on group title clicks
         */
        $(document).on('click', '#wp-admin-bar-unno-hidden-notices-panel .unno-group-title > .ab-item', function(e) {
            e.preventDefault();
            return false;
        });

        /**
         * Keyboard accessibility
         */
        $(document).on('keypress', '.unno-panel-restore-link', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).trigger('click');
            }
        });
    });

})(jQuery);

