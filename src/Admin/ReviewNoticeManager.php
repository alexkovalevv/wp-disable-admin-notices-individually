<?php

declare(strict_types=1);

namespace UNNO\Admin;

/**
 * Review Notice Manager for Unnotifier plugin.
 * 
 * Displays a beautiful notice encouraging users to rate the plugin,
 * share ideas, and provide feedback.
 * 
 * @package UNNO\Admin
 * @since 1.2.5
 */
if (!defined('ABSPATH')) {
    exit;
}

class ReviewNoticeManager {
    
    /**
     * Notice ID for dismissal tracking.
     * 
     * @since 1.2.5
     */
    private const NOTICE_ID = 'unno_review_notice_v125';
    
    /**
     * Meta key for dismissal count.
     * 
     * @since 1.2.5
     */
    private const DISMISSAL_COUNT_KEY = 'unno_review_notice_dismissal_count';
    
    /**
     * Meta key for last dismissal time.
     * 
     * @since 1.2.5
     */
    private const LAST_DISMISSAL_TIME_KEY = 'unno_review_notice_last_dismissal_time';
    
    /**
     * Option key for plugin activation time.
     * 
     * @since 1.2.5
     */
    private const ACTIVATION_TIME_KEY = 'unno_activation_time';
    
    /**
     * Minimum days since plugin activation to show notice first time.
     * 
     * @since 1.2.5
     */
    private const MIN_DAYS_AFTER_ACTIVATION = 7;
    
    /**
     * Days to wait after first dismissal (1 month).
     * 
     * @since 1.2.5
     */
    private const DAYS_AFTER_FIRST_DISMISSAL = 30;
    
    /**
     * Days to wait after second dismissal (3 months).
     * 
     * @since 1.2.5
     */
    private const DAYS_AFTER_SECOND_DISMISSAL = 90;
    
    /**
     * Maximum number of times to show notice.
     * 
     * @since 1.2.5
     */
    private const MAX_DISMISSAL_COUNT = 2;
    
    /**
     * Initialize review notice functionality.
     * 
     * @since 1.2.5
     * @return void
     */
    public function init(): void {
        // Only show in admin area
        if (!is_admin()) {
            return;
        }
        
        // Check if notice should be displayed
        if (!$this->should_show_notice()) {
            return;
        }
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Display notice using anonymous function to bypass is_own_callback() check
        add_action('admin_notices', function() {
            $this->render_notice();
        });
        
        // AJAX handler for dismissing notice
        add_action('wp_ajax_unno_dismiss_review_notice', [$this, 'handle_dismiss_notice']);
    }
    
    /**
     * Check if notice should be displayed.
     * 
     * @since 1.2.5
     * @return bool True if notice should be shown
     */
    private function should_show_notice(): bool {
        // Debug mode: force show notice if UNNO_REVIEW_NOTICE_DEBUG is true
        if (defined('UNNO_REVIEW_NOTICE_DEBUG') && UNNO_REVIEW_NOTICE_DEBUG === true) {
            return true;
        }
        
        $user_id = get_current_user_id();
        
        // Check user permissions
        if (!$user_id || !current_user_can('manage_options')) {
            return false;
        }
        
        // Get dismissal count
        $dismissal_count = (int) get_user_meta($user_id, self::DISMISSAL_COUNT_KEY, true);
        
        // If user dismissed notice more than MAX_DISMISSAL_COUNT times, don't show again
        if ($dismissal_count > self::MAX_DISMISSAL_COUNT) {
            return false;
        }
        
        // First time: check activation time
        if ($dismissal_count === 0) {
            $activation_time = get_option(self::ACTIVATION_TIME_KEY);
            if (!$activation_time) {
                // Set activation time if not set and return true for immediate display when MIN_DAYS = 0
                update_option(self::ACTIVATION_TIME_KEY, time());
                // If MIN_DAYS is 0, show immediately
                if (self::MIN_DAYS_AFTER_ACTIVATION === 0) {
                    return true;
                }
                return false;
            }
            
            $days_since_activation = (time() - (int) $activation_time) / DAY_IN_SECONDS;
            if ($days_since_activation < self::MIN_DAYS_AFTER_ACTIVATION) {
                return false;
            }
            
            return true;
        }
        
        // After dismissal: check time since last dismissal
        $last_dismissal_time = get_user_meta($user_id, self::LAST_DISMISSAL_TIME_KEY, true);
        if (!$last_dismissal_time) {
            // If dismissal count > 0 but no last dismissal time, something went wrong
            // Reset dismissal count and show notice after activation period
            delete_user_meta($user_id, self::DISMISSAL_COUNT_KEY);
            return $this->should_show_notice();
        }
        
        // Determine days to wait based on dismissal count
        // After 1st dismissal (count = 1): wait 30 days
        // After 2nd dismissal (count = 2): wait 90 days
        $days_to_wait = $dismissal_count === 1 
            ? self::DAYS_AFTER_FIRST_DISMISSAL 
            : self::DAYS_AFTER_SECOND_DISMISSAL;
        
        $days_since_dismissal = (time() - (int) $last_dismissal_time) / DAY_IN_SECONDS;
        
        return $days_since_dismissal >= $days_to_wait;
    }
    
    /**
     * Enqueue assets for review notice.
     * 
     * @since 1.2.5
     * @return void
     */
    public function enqueue_assets(): void {
        // Styles are loaded via main plugin stylesheet (admin.css)
        // Add inline JavaScript for dismiss functionality
        add_action('admin_footer', [$this, 'render_notice_script']);
    }
    
    /**
     * Render review notice HTML.
     * 
     * @since 1.2.5
     * @return void
     */
    public function render_notice(): void {
        // Ensure this notice is not hidden by Unnotifier itself
        // Add data attribute to prevent it from being processed by Unnotifier's notice handler
        ?>
        <div class="unno-review-notice notice notice-info is-dismissible" 
             id="<?php echo esc_attr(self::NOTICE_ID); ?>"
             data-unno-ignore="true">
            <div class="unno-review-notice__content">
                <div class="unno-review-notice__icon">
                    <span class="dashicons dashicons-star-filled"></span>
                </div>
                <div class="unno-review-notice__text">
                    <h3 class="unno-review-notice__title">
                        <?php esc_html_e('Enjoying Unnotifier?', 'unnotifier'); ?>
                    </h3>
                    <p class="unno-review-notice__message">
                        <?php 
                        printf(
                            esc_html__('If you find Unnotifier helpful, we would be thrilled if you could %1$srate us 5 stars%2$s on WordPress.org! Your feedback helps us improve and reach more users.', 'unnotifier'),
                            '<strong>',
                            '</strong>'
                        );
                        ?>
                    </p>
                    <p class="unno-review-notice__message">
                        <?php esc_html_e('We also love hearing about your ideas and experiences using the plugin. Share your thoughts and help shape the future of Unnotifier!', 'unnotifier'); ?>
                    </p>
                    <div class="unno-review-notice__actions">
                        <a href="https://wordpress.org/support/plugin/unnotifier/reviews/#new-post" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="button button-primary unno-review-notice__button">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php esc_html_e('Rate 5 Stars', 'unnotifier'); ?>
                        </a>
                        <a href="https://wordpress.org/support/plugin/unnotifier/" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="button button-secondary unno-review-notice__button">
                            <span class="dashicons dashicons-format-chat"></span>
                            <?php esc_html_e('Share Ideas & Feedback', 'unnotifier'); ?>
                        </a>
                        <button type="button" 
                                class="notice-dismiss unno-review-notice__dismiss" 
                                data-notice-id="<?php echo esc_attr(self::NOTICE_ID); ?>">
                            <span class="screen-reader-text"><?php esc_html_e('Dismiss this notice', 'unnotifier'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render inline JavaScript for dismiss functionality.
     * 
     * @since 1.2.5
     * @return void
     */
    public function render_notice_script(): void {
        $nonce = wp_create_nonce('unno_dismiss_review_notice');
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $(document).on('click', '.unno-review-notice__dismiss', function(e) {
                e.preventDefault();
                
                var $notice = $(this).closest('.unno-review-notice');
                var noticeId = $(this).data('notice-id');
                
                // Hide notice with animation
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
                
                // Dismiss notice via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'unno_dismiss_review_notice',
                        notice_id: noticeId,
                        _wpnonce: <?php echo wp_json_encode($nonce); ?>
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Handle AJAX request to dismiss review notice.
     * 
     * @since 1.2.5
     * @return void
     */
    public function handle_dismiss_notice(): void {
        // Verify nonce
        if (!check_ajax_referer('unno_dismiss_review_notice', '_wpnonce', false)) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'unnotifier')]);
            return;
        }
        
        $notice_id = sanitize_text_field($_POST['notice_id'] ?? '');
        
        if (empty($notice_id) || $notice_id !== self::NOTICE_ID) {
            wp_send_json_error(['message' => __('Invalid notice ID.', 'unnotifier')]);
            return;
        }
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'unnotifier')]);
            return;
        }
        
        // Get current dismissal count
        $dismissal_count = (int) get_user_meta($user_id, self::DISMISSAL_COUNT_KEY, true);
        
        // Increment dismissal count
        $dismissal_count++;
        update_user_meta($user_id, self::DISMISSAL_COUNT_KEY, $dismissal_count);
        
        // Save last dismissal time
        update_user_meta($user_id, self::LAST_DISMISSAL_TIME_KEY, time());
        
        // Also save legacy dismissal flag for compatibility
        update_user_meta($user_id, self::NOTICE_ID, '1');
        
        wp_send_json_success([
            'message' => __('Notice dismissed.', 'unnotifier'),
            'dismissal_count' => $dismissal_count
        ]);
    }
}

