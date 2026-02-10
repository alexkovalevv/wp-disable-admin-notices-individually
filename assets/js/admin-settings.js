/**
 * Disable Admin Notices Individually - Admin Settings JavaScript
 *
 * Handles AJAX functionality for settings page
 */

(function ($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function () {
        
        // Handle reset notices functionality
        function sendReset(target, $btn, texts) {
            $btn.prop('disabled', true).text(texts.progress);
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'unno_reset_notices',
                    nonce: unno_ajax.nonce,
                    action_type: target // 'user' | 'all'
                }
            }).done(function (response) {
                if (response && response.success) {
                    var msg = response.data && response.data.message ? response.data.message : 'Done.';
                    $('#unno-reset-message')
                        .html('<div class="notice notice-success inline"><p>' + msg + '</p></div>')
                        .show();

                    // If a new counter comes, update the description under the corresponding button
                    if (target === 'all' && response.data && typeof response.data.hidden_global_count !== 'undefined') {
                        var $desc = $('#unno-reset-notices-all').closest('.unno-reset-group').find('.description');
                        $desc.text('Currently ' + response.data.hidden_global_count + ' notices are hidden for all users. Click to restore them.');
                    }

                    if (target === 'user' && response.data && typeof response.data.hidden_user_count !== 'undefined') {
                        var $desc = $('#unno-reset-notices-user').closest('.unno-reset-group').find('.description');
                        $desc.text('Currently ' + response.data.hidden_user_count + ' notices are hidden for your account. Click to show them again.');
                    }

                    setTimeout(function () {
                        location.reload();
                    }, 1200);
                } else {
                    var msg = (response && response.data) ? response.data : 'An error occurred. Please try again.';
                    $('#unno-reset-message').html('<div class="notice notice-error inline"><p>' + msg + '</p></div>').show();
                }
            }).fail(function () {
                $('#unno-reset-message')
                    .html('<div class="notice notice-error inline"><p>An error occurred. Please try again.</p></div>')
                    .show();
            }).always(function () {
                $btn.prop('disabled', false).text(texts.default);
            });
        }

        // Reset user notices
        $('#unno-reset-notices-user').on('click', function () {
            sendReset('user', $(this), {
                progress: 'Resetting...',
                default: 'Reset My Hidden Notices'
            });
        });

        // Reset all notices
        $('#unno-reset-notices-all').on('click', function () {
            sendReset('all', $(this), {
                progress: 'Resetting...',
                default: 'Reset Hidden Notices For All'
            });
        });

        // Handle single notice restore
        $(document).on('click', '.unno-restore-single-notice', function () {
            var $btn = $(this);
            var noticeId = $btn.data('notice-id');
            var target = $btn.data('target');
            
            if (!noticeId) {
                return;
            }

            // Find the table row - try multiple selectors to be safe
            var $noticeRow = $btn.closest('tr');
            if (!$noticeRow.length) {
                $noticeRow = $btn.closest('.unno-notice-row, .unno-notice-item');
            }
            
            // If still not found, try finding by notice ID in the row
            if (!$noticeRow.length) {
                $noticeRow = $btn.closest('tbody').find('tr').filter(function() {
                    return $(this).find('[data-notice-id="' + noticeId + '"]').length > 0;
                });
            }

            // Store original button HTML
            var originalHtml = $btn.html();

            // Disable button and show loading
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> ' + (typeof unno_ajax.restoring_text !== 'undefined' ? unno_ajax.restoring_text : 'Restoring...'));

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'unno_restore_single_notice',
                    nonce: unno_ajax.nonce,
                    notice_id: noticeId,
                    action_type: target
                }
            }).done(function (response) {
                if (response && response.success) {
                    // Show success message
                    var msg = '';
                    if (response.data) {
                        if (typeof response.data === 'string') {
                            msg = response.data;
                        } else if (response.data.message) {
                            msg = response.data.message;
                        }
                    }
                    if (!msg && typeof unno_ajax.restore_success_text !== 'undefined') {
                        msg = unno_ajax.restore_success_text;
                    }
                    if (!msg) {
                        msg = 'Notice restored.';
                    }
                    $('#unno-reset-message')
                        .html('<div class="notice notice-success inline"><p>' + msg + '</p></div>')
                        .show();

                    // Remove the notice row/item
                    if ($noticeRow.length) {
                        // Add removing class and animate out
                        $noticeRow.addClass('removing');
                        
                        // Fade out and remove
                        $noticeRow.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if table is now empty and reload if needed
                            var $table = $('.wp-list-table.hiddennotices');
                            if ($table.length) {
                                var remainingRows = $table.find('tbody tr').length;
                                if (remainingRows === 0) {
                                    // Reload page to refresh the table
                                    setTimeout(function() {
                                        location.reload();
                                    }, 500);
                                } else {
                                    // Update pagination info if needed
                                    var $pagination = $('.displaying-num');
                                    if ($pagination.length) {
                                        $pagination.text(remainingRows + ' ' + (remainingRows === 1 ? 'item' : 'items'));
                                    }
                                }
                            }
                        });
                    } else {
                        // If row not found, reload page to refresh table
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    }

                    // Update counters
                    if (target === 'global' && response.data && typeof response.data.hidden_global_count !== 'undefined') {
                        var $desc = $('#unno-reset-notices-all').closest('.unno-reset-group').find('.description');
                        $desc.text('Currently ' + response.data.hidden_global_count + ' notices are hidden for all users. Click to restore them.');

                        // Hide details if no more notices
                        if (response.data.hidden_global_count === 0) {
                            setTimeout(function () {
                                $('#unno-reset-notices-all').closest('.unno-reset-group').find('.unno-hidden-notices-details').fadeOut();
                            }, 500);
                        }
                    }

                    if (target === 'user' && response.data && typeof response.data.hidden_user_count !== 'undefined') {
                        var $desc = $('#unno-reset-notices-user').closest('.unno-reset-group').find('.description');
                        $desc.text('Currently ' + response.data.hidden_user_count + ' notices are hidden for your account. Click to show them again.');

                        // Hide details if no more notices
                        if (response.data.hidden_user_count === 0) {
                            setTimeout(function () {
                                $('#unno-reset-notices-user').closest('.unno-reset-group').find('.unno-hidden-notices-details').fadeOut();
                            }, 500);
                        }
                    }

                    // Auto-hide message after 3 seconds
                    setTimeout(function () {
                        $('#unno-reset-message').fadeOut();
                    }, 3000);
                } else {
                    var msg = '';
                    if (response && response.data) {
                        msg = typeof response.data === 'string' ? response.data : (response.data.message || '');
                    }
                    if (!msg && typeof unno_ajax.restore_error_text !== 'undefined') {
                        msg = unno_ajax.restore_error_text;
                    }
                    if (!msg) {
                        msg = 'Failed to restore notice.';
                    }
                    $('#unno-reset-message').html('<div class="notice notice-error inline"><p>' + msg + '</p></div>').show();

                    // Re-enable button
                    $btn.prop('disabled', false).html(originalHtml);
                }
            }).fail(function () {
                $('#unno-reset-message')
                    .html('<div class="notice notice-error inline"><p>Network error occurred. Please try again.</p></div>')
                    .show();

                // Re-enable button
                $btn.prop('disabled', false).html(originalHtml);
            });
        });

        // Add rotation animation for loading spinner
        $('<style>@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }</style>').appendTo('head');
    });

})(jQuery);
