/**
 * Disable Admin Notices Individually - Admin JavaScript
 *
 * Handles AJAX functionality for hiding notices
 */

(function ($) {
    'use strict';

    // Wait for DOM to be ready
    $(document).ready(function () {

        // Handle hide notice button clicks
        $(document).on('click', '.dani-hide-notice', function (e) {
            e.preventDefault();

            var $button = $(this);
            var $notice = $button.closest('.notice, .dani-notice-wrapper, [id*="message"], [class*="message"], [class*="updated"], [class*="error"]');
            var noticeId = $button.data('notice-id');
            var target = $button.data('target') || 'user';

            // Validate notice ID
            if (!noticeId) {
                console.error('DANI: No notice ID found');
                return;
            }

            // Prevent multiple clicks
            if ($button.hasClass('loading')) {
                return;
            }

            // Collect additional notice metadata
            var noticeData = collectNoticeMetadata($notice);

            // Add loading state
            $button.addClass('loading');
            $button.prop('disabled', true);
            $notice.addClass('dani-notice-hiding');

            // Store original button text
            var originalText = $button.text();
            $button.text('...');

            // Send AJAX request with extended data
            $.ajax({
                url: dani_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dani_hide_notice',
                    notice_id: noticeId,
                    action_type: target,
                    nonce: dani_ajax.nonce,
                    source_plugin: noticeData.sourcePlugin,
                    notice_content: noticeData.content,
                    notice_excerpt: noticeData.excerpt
                },
                success: function (response) {
                    if (response.success) {
                        // Hide notice with animation
                        $notice.fadeOut(300, function () {
                            $notice.remove();
                        });

                        // Не показываем сообщение об успешном скрытии
                    } else {
                        // Show error message
                        showMessage('error', response.data || 'Failed to hide notice');

                        // Reset button state
                        resetButton($button, $notice, originalText);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('DANI AJAX Error:', error);
                    showMessage('error', 'Network error occurred. Please try again.');

                    // Reset button state
                    resetButton($button, $notice, originalText);
                },
                complete: function () {
                    // This will only run if the notice wasn't removed
                    if ($notice.length && $notice.is(':visible')) {
                        $button.removeClass('loading');
                        $button.prop('disabled', false);
                    }
                }
            });
        });

        /**
         * Collect metadata about a notice for storage
         * 
         * @param {jQuery} $notice jQuery object of the notice element
         * @returns {Object} Object containing notice metadata
         */
        function collectNoticeMetadata($notice) {
            var metadata = {
                sourcePlugin: 'Unknown Plugin',
                content: '',
                excerpt: ''
            };

            try {
                // Try to get source plugin from plugin info element first (our plugin's determined source)
                var $pluginInfo = $notice.find('.dani-plugin-info strong');
                if ($pluginInfo.length) {
                    metadata.sourcePlugin = $pluginInfo.text().trim();
                } else {
                    // Enhanced plugin detection from various sources
                    var classes = $notice.attr('class') || '';
                    var id = $notice.attr('id') || '';
                    var dataPlugin = $notice.attr('data-plugin') || '';
                    var noticeContent = $notice.text().toLowerCase();
                    
                    // Check data attributes first
                    if (dataPlugin) {
                        metadata.sourcePlugin = dataPlugin;
                    }
                    // Check ID patterns
                    else if (id.includes('woocommerce')) {
                        metadata.sourcePlugin = 'WooCommerce';
                    } else if (id.includes('jetpack')) {
                        metadata.sourcePlugin = 'Jetpack';
                    } else if (id.includes('yoast')) {
                        metadata.sourcePlugin = 'Yoast SEO';
                    } else if (id.includes('elementor')) {
                        metadata.sourcePlugin = 'Elementor';
                    } else if (id.includes('updraft')) {
                        metadata.sourcePlugin = 'UpdraftPlus';
                    } else if (id.includes('wordfence')) {
                        metadata.sourcePlugin = 'Wordfence';
                    }
                    // Check CSS classes
                    else if (classes.includes('woocommerce')) {
                        metadata.sourcePlugin = 'WooCommerce';
                    } else if (classes.includes('jetpack')) {
                        metadata.sourcePlugin = 'Jetpack';
                    } else if (classes.includes('yoast')) {
                        metadata.sourcePlugin = 'Yoast SEO';
                    } else if (classes.includes('elementor')) {
                        metadata.sourcePlugin = 'Elementor';
                    } else if (classes.includes('updraftplus')) {
                        metadata.sourcePlugin = 'UpdraftPlus';
                    } else if (classes.includes('wordfence')) {
                        metadata.sourcePlugin = 'Wordfence';
                    } else if (classes.includes('akismet')) {
                        metadata.sourcePlugin = 'Akismet';
                    }
                    // Check content patterns
                    else if (noticeContent.includes('woocommerce')) {
                        metadata.sourcePlugin = 'WooCommerce';
                    } else if (noticeContent.includes('jetpack')) {
                        metadata.sourcePlugin = 'Jetpack';
                    } else if (noticeContent.includes('yoast')) {
                        metadata.sourcePlugin = 'Yoast SEO';
                    } else if (noticeContent.includes('elementor')) {
                        metadata.sourcePlugin = 'Elementor';
                    } else if (noticeContent.includes('updraft')) {
                        metadata.sourcePlugin = 'UpdraftPlus';
                    } else if (noticeContent.includes('wordfence')) {
                        metadata.sourcePlugin = 'Wordfence';
                    } else if (noticeContent.includes('plugin')) {
                        // Try to extract plugin name from text like "Plugin XYZ says:"
                        var pluginMatch = noticeContent.match(/plugin\s+([a-z0-9\s]+)/i);
                        if (pluginMatch && pluginMatch[1]) {
                            var pluginName = pluginMatch[1].trim();
                            if (pluginName.length > 2 && pluginName.length < 50) {
                                metadata.sourcePlugin = pluginName.charAt(0).toUpperCase() + pluginName.slice(1);
                            }
                        }
                    }
                }

                // Get notice content, excluding our plugin controls
                var $contentClone = $notice.clone();
                $contentClone.find('.dani-hide-button-wrapper').remove();
                $contentClone.find('script').remove();
                $contentClone.find('style').remove();
                
                var rawContent = $contentClone.html() || '';
                metadata.content = rawContent;
                
                // Create excerpt by stripping HTML and limiting length
                var tempDiv = document.createElement('div');
                tempDiv.innerHTML = rawContent;
                var plainText = (tempDiv.textContent || tempDiv.innerText || '').trim();
                
                // Remove extra whitespace and line breaks
                plainText = plainText.replace(/\s+/g, ' ').trim();
                
                // Limit to 150 characters
                if (plainText.length > 150) {
                    metadata.excerpt = plainText.substring(0, 147) + '...';
                } else {
                    metadata.excerpt = plainText;
                }
                
                // If excerpt is empty, try to get some meaningful text
                if (!metadata.excerpt) {
                    var $firstP = $contentClone.find('p:first');
                    if ($firstP.length) {
                        var pText = $firstP.text().trim();
                        if (pText.length > 150) {
                            metadata.excerpt = pText.substring(0, 147) + '...';
                        } else {
                            metadata.excerpt = pText;
                        }
                    }
                }
                
                // Final fallback
                if (!metadata.excerpt) {
                    metadata.excerpt = 'Notice content';
                }

            } catch (error) {
                console.warn('DANI: Error collecting notice metadata:', error);
                metadata.excerpt = 'Unable to extract notice content';
            }

            return metadata;
        }

        /**
         * Reset button to original state
         */
        function resetButton($button, $notice, originalText) {
            $button.removeClass('loading');
            $button.prop('disabled', false);
            $button.text(originalText);
            $notice.removeClass('dani-notice-hiding');
        }

        /**
         * Show temporary message
         */
        function showMessage(type, message) {
            var $message = $('<div class="notice notice-' + type + ' is-dismissible dani-temp-message"><p>' + message + '</p></div>');

            // Insert message at the top of the admin content
            if ($('.wrap h1').length) {
                $message.insertAfter('.wrap h1').first();
            } else if ($('#wpbody-content .wrap').length) {
                $message.prependTo('#wpbody-content .wrap').first();
            } else {
                $message.prependTo('#wpbody-content');
            }

            // Auto-hide messages
            setTimeout(function () {
                $message.fadeOut(300, function () {
                    $message.remove();
                });
            }, 5000);

            // Handle dismiss button
            $message.on('click', '.notice-dismiss', function () {
                $message.fadeOut(300, function () {
                    $message.remove();
                });
            });
        }

        /**
         * Add keyboard support for hide buttons
         */
        $(document).on('keydown', '.dani-hide-notice', function (e) {
            // Trigger click on Enter or Space
            if (e.which === 13 || e.which === 32) {
                e.preventDefault();
                $(this).click();
            }
        });

        /**
         * Improve accessibility
         */
        $('.dani-hide-notice').each(function () {
            var $button = $(this);

            // Add ARIA attributes
            $button.attr({
                'aria-label': dani_ajax.hide_text,
                'role': 'link',
                'tabindex': '0'
            });
        });

        /**
         * Handle window resize for responsive adjustments
         */
        var resizeTimer;
        $(window).on('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                // Adjust button positions if needed
                $('.dani-hide-button-wrapper').each(function () {
                    var $wrapper = $(this);
                    var $notice = $wrapper.closest('.notice');

                    // Ensure proper positioning
                    if ($notice.length) {
                        $notice.css('position', 'relative');
                    }
                });
            }, 250);
        });

        /**
         * Debug mode logging
         */
        if (window.console && typeof dani_ajax.debug !== 'undefined' && dani_ajax.debug) {
            console.log('DANI: Admin script loaded');
            console.log('DANI: AJAX URL:', dani_ajax.ajax_url);
            console.log('DANI: Found hide buttons:', $('.dani-hide-notice').length);
        }
    });

})(jQuery);