<?php

namespace DANI\Admin;

/**
 * Settings and configuration for Disable Admin Notices Individually
 */
if (!defined('ABSPATH')) {
    exit;
}

use DANI\Data\Options;

if (!class_exists('DANI\Admin\Settings')) {
    class Settings
    {
        public static function init()
        {
            if (is_admin()) {
                add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
                add_action('admin_init', [__CLASS__, 'admin_init']);
                add_action('admin_post_dani_download_log', [__CLASS__, 'download_log']);
            }
        }

        public static function add_admin_menu()
        {
            add_options_page(
                    __('Disable Admin Notices', 'disable-admin-notices-individually'),
                    __('Admin Notices', 'disable-admin-notices-individually'),
                    'manage_options',
                    'disable-admin-notices-individually',
                    [__CLASS__, 'admin_page']
            );
        }

        public static function admin_init()
        {
            register_setting('dani_settings', 'dani_settings', [__CLASS__, 'sanitize_settings']);

            add_settings_section(
                    'dani_main_section',
                    __('Notice Display Settings', 'disable-admin-notices-individually'),
                    [__CLASS__, 'section_callback'],
                    'dani_settings'
            );

            add_settings_field(
                    'mode',
                    __('Notice Mode', 'disable-admin-notices-individually'),
                    [__CLASS__, 'mode_field_callback'],
                    'dani_settings',
                    'dani_main_section'
            );

            add_settings_field(
                    'reset_notices',
                    __('Reset Hidden Notices', 'disable-admin-notices-individually'),
                    [__CLASS__, 'reset_field_callback'],
                    'dani_settings',
                    'dani_main_section'
            );

            add_settings_field(
                    'debug',
                    __('Debug mode', 'disable-admin-notices-individually'),
                    [__CLASS__, 'debug_field_callback'],
                    'dani_settings',
                    'dani_main_section'
            );
        }

        public static function section_callback()
        {
            echo '<p>' . esc_html__('Configure how admin notices are displayed in your WordPress dashboard.', 'disable-admin-notices-individually') . '</p>';
        }

        public static function mode_field_callback()
        {
            $options_instance = Options::instance();
            $mode = $options_instance->get('mode', 'individual');

            ?>
            <fieldset>
                <label>
                    <input type="radio" name="dani_settings[mode]"
                           value="show_all" <?php checked($mode, 'show_all'); ?>>
                    <?php esc_html_e('Show all notifications', 'disable-admin-notices-individually'); ?>
                </label><br>
                <p class="description"><?php esc_html_e('Display all admin notifications normally without any hiding options.', 'disable-admin-notices-individually'); ?></p>

                <label>
                    <input type="radio" name="dani_settings[mode]"
                           value="individual" <?php checked($mode, 'individual'); ?>>
                    <?php esc_html_e('Hide notifications individually', 'disable-admin-notices-individually'); ?>
                </label><br>
                <p class="description"><?php esc_html_e('Show all notifications with a "hide forever" button on each one for individual control.', 'disable-admin-notices-individually'); ?></p>

                <label>
                    <input type="radio" name="dani_settings[mode]"
                           value="hide_all" <?php checked($mode, 'hide_all'); ?>>
                    <?php esc_html_e('Hide all notifications', 'disable-admin-notices-individually'); ?>
                </label>
                <p class="description"><?php esc_html_e('Hide all admin notifications completely.', 'disable-admin-notices-individually'); ?></p>
            </fieldset>
            <?php
        }

        public static function reset_field_callback()
        {
            $options_instance = Options::instance();

            // Глобально скрытые уведомления (для всех)
            $hidden_global = $options_instance->get_global_hidden_notices();
            $hidden_global_count = count($hidden_global);

            // Персонально скрытые уведомления текущего пользователя
            $hidden_user = $options_instance->get_user_hidden_notices();
            $hidden_user_count = count($hidden_user);

            $nonce = wp_create_nonce('dani_nonce');
            ?>
            <div class="dani-reset-group">
                <h4><?php esc_html_e('Per-user hidden notices', 'disable-admin-notices-individually'); ?></h4>
                <button type="button" id="dani-reset-notices-user" class="button button-secondary">
                    <?php esc_html_e('Reset My Hidden Notices', 'disable-admin-notices-individually'); ?>
                </button>
                <p class="description">
                    <?php
                    // translators: %d is the number of notices currently hidden for the user
                    printf(esc_html__('Currently %d notices are hidden for your account. Click to show them again.', 'disable-admin-notices-individually'), intval($hidden_user_count)); ?>
                </p>

                <?php if ($hidden_user_count > 0): ?>
                    <details class="dani-hidden-notices-details">
                        <summary><?php esc_html_e('Show hidden notices list', 'disable-admin-notices-individually'); ?></summary>
                        <div class="dani-hidden-notices-table-wrapper">
                            <table class="dani-hidden-notices-table">
                                <thead>
                                <tr>
                                    <th class="dani-plugin-column"><?php esc_html_e('Plugin', 'disable-admin-notices-individually'); ?></th>
                                    <th class="dani-content-column"><?php esc_html_e('Notice Content', 'disable-admin-notices-individually'); ?></th>
                                    <th class="dani-actions-column"><?php esc_html_e('Actions', 'disable-admin-notices-individually'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($hidden_user as $notice_id => $notice_data): ?>
                                    <?php
                                    // Handle both old format (simple array) and new format (associative array with metadata)
                                    if (is_array($notice_data)) {
                                        $source_plugin = $notice_data['source_plugin'] ?? 'WordPress Admin';
                                        $excerpt = $notice_data['excerpt'] ?? 'Administrative notice';
                                    } else {
                                        // Old format - notice_data is actually notice_id
                                        $notice_id = $notice_data;
                                        $source_plugin = 'WordPress Admin';
                                        $excerpt = 'Legacy notice - ' . substr($notice_id, 0, 20);
                                    }
                                    ?>
                                    <tr class="dani-notice-row" data-notice-id="<?php echo esc_attr($notice_id); ?>">
                                        <td class="dani-plugin-cell">
                                            <strong><?php echo esc_html($source_plugin); ?></strong>
                                        </td>
                                        <td class="dani-content-cell">
                                        <span class="dani-notice-excerpt" title="<?php echo esc_attr($excerpt); ?>">
                                            <?php echo esc_html(mb_strlen($excerpt) > 80 ? mb_substr($excerpt, 0, 77) . '...' : $excerpt); ?>
                                        </span>
                                        </td>
                                        <td class="dani-actions-cell">
                                            <button type="button" class="button button-small dani-restore-single-notice"
                                                    data-target="user"
                                                    data-notice-id="<?php echo esc_attr($notice_id); ?>">
                                                <span class="dashicons dashicons-undo"></span>
                                                <?php esc_html_e('Restore', 'disable-admin-notices-individually'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endif; ?>
            </div>

            <?php if (current_user_can('manage_options')): ?>
            <hr>
            <div class="dani-reset-group">
                <h4><?php esc_html_e('Global hidden notices', 'disable-admin-notices-individually'); ?></h4>
                <button type="button" id="dani-reset-notices-all" class="button button-secondary">
                    <?php esc_html_e('Reset Hidden Notices For All', 'disable-admin-notices-individually'); ?>
                </button>
                <p class="description">
                    <?php
                    // translators: %d is the number of notices currently hidden for all users
                    printf(esc_html__('Currently %d notices are hidden for all users. Click to restore them.', 'disable-admin-notices-individually'), intval($hidden_global_count)); ?>
                </p>

                <?php if ($hidden_global_count > 0): ?>
                    <details class="dani-hidden-notices-details">
                        <summary><?php esc_html_e('Show hidden notices list', 'disable-admin-notices-individually'); ?></summary>
                        <div class="dani-hidden-notices-table-wrapper">
                            <table class="dani-hidden-notices-table">
                                <thead>
                                <tr>
                                    <th class="dani-plugin-column"><?php esc_html_e('Plugin', 'disable-admin-notices-individually'); ?></th>
                                    <th class="dani-content-column"><?php esc_html_e('Notice Content', 'disable-admin-notices-individually'); ?></th>
                                    <th class="dani-user-column"><?php esc_html_e('Hidden By', 'disable-admin-notices-individually'); ?></th>
                                    <th class="dani-actions-column"><?php esc_html_e('Actions', 'disable-admin-notices-individually'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($hidden_global as $notice_id => $notice_data): ?>
                                    <?php
                                    // Handle both old format (timestamp only) and new format (associative array with metadata)
                                    if (is_array($notice_data)) {
                                        $source_plugin = $notice_data['source_plugin'] ?? 'WordPress Admin';
                                        $excerpt = $notice_data['excerpt'] ?? 'Administrative notice';
                                        $hidden_by_user_id = $notice_data['hidden_by_user_id'] ?? null;
                                    } else {
                                        // Old format - notice_data is timestamp
                                        $source_plugin = 'WordPress Admin';
                                        $excerpt = 'Legacy global notice - ' . substr($notice_id, 0, 20);
                                        $hidden_by_user_id = null;
                                    }

                                    // Get user display name if available
                                    $hidden_by_user = __('Unknown', 'disable-admin-notices-individually');
                                    if ($hidden_by_user_id) {
                                        $user = get_userdata($hidden_by_user_id);
                                        if ($user) {
                                            $hidden_by_user = $user->display_name ?: $user->user_login;
                                        }
                                    }
                                    ?>
                                    <tr class="dani-notice-row" data-notice-id="<?php echo esc_attr($notice_id); ?>">
                                        <td class="dani-plugin-cell">
                                            <strong><?php echo esc_html($source_plugin); ?></strong>
                                        </td>
                                        <td class="dani-content-cell">
                                        <span class="dani-notice-excerpt" title="<?php echo esc_attr($excerpt); ?>">
                                            <?php echo esc_html(mb_strlen($excerpt) > 80 ? mb_substr($excerpt, 0, 77) . '...' : $excerpt); ?>
                                        </span>
                                        </td>
                                        <td class="dani-user-cell">
                                            <span class="dani-hidden-user"><?php echo esc_html($hidden_by_user); ?></span>
                                        </td>
                                        <td class="dani-actions-cell">
                                            <button type="button" class="button button-small dani-restore-single-notice"
                                                    data-target="global"
                                                    data-notice-id="<?php echo esc_attr($notice_id); ?>">
                                                <span class="dashicons dashicons-undo"></span>
                                                <?php esc_html_e('Restore', 'disable-admin-notices-individually'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>

            <div id="dani-reset-message" style="display:none;"></div>

            <script>
                jQuery(function ($) {
                    function sendReset(target, $btn, texts) {
                        $btn.prop('disabled', true).text(texts.progress);
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'dani_reset_notices',
                                nonce: '<?php echo esc_js($nonce); ?>',
                                action_type: target // 'user' | 'all'
                            }
                        }).done(function (response) {
                            if (response && response.success) {
                                var msg = response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Done.', 'disable-admin-notices-individually')); ?>';
                                $('#dani-reset-message')
                                    .html('<div class="notice notice-success inline"><p>' + msg + '</p></div>')
                                    .show();

                                // If a new counter comes, update the description under the corresponding button
                                if (target === 'all' && response.data && typeof response.data.hidden_global_count !== 'undefined') {
                                    var $desc = $('#dani-reset-notices-all').closest('.dani-reset-group').find('.description');
                                    <?php /* translators: %d is the number of notices hidden for all users */ ?>
                                    $desc.text('<?php echo esc_js(__('Currently %d notices are hidden for all users. Click to restore them.', 'disable-admin-notices-individually')); ?>'
                                        .replace('%d', response.data.hidden_global_count));
                                }

                                if (target === 'user' && response.data && typeof response.data.hidden_user_count !== 'undefined') {
                                    var $desc = $('#dani-reset-notices-user').closest('.dani-reset-group').find('.description');
                                    <?php /* translators: %d is the number of notices hidden for the current user */ ?>
                                    $desc.text('<?php echo esc_js(__('Currently %d notices are hidden for your account. Click to show them again.', 'disable-admin-notices-individually')); ?>'
                                        .replace('%d', response.data.hidden_user_count));
                                }

                                setTimeout(function () {
                                    location.reload();
                                }, 1200);
                            } else {
                                var msg = (response && response.data) ? response.data : '<?php echo esc_js(__('An error occurred. Please try again.', 'disable-admin-notices-individually')); ?>';
                                $('#dani-reset-message').html('<div class="notice notice-error inline"><p>' + msg + '</p></div>').show();
                            }
                        }).fail(function () {
                            $('#dani-reset-message')
                                .html('<div class="notice notice-error inline"><p><?php echo esc_js(__('An error occurred. Please try again.', 'disable-admin-notices-individually')); ?></p></div>')
                                .show();
                        }).always(function () {
                            $btn.prop('disabled', false).text(texts.default);
                        });
                    }

                    $('#dani-reset-notices-user').on('click', function () {
                        sendReset('user', $(this), {
                            progress: '<?php echo esc_js(__('Resetting...', 'disable-admin-notices-individually')); ?>',
                            default: '<?php echo esc_js(__('Reset My Hidden Notices', 'disable-admin-notices-individually')); ?>'
                        });
                    });

                    $('#dani-reset-notices-all').on('click', function () {
                        sendReset('all', $(this), {
                            progress: '<?php echo esc_js(__('Resetting...', 'disable-admin-notices-individually')); ?>',
                            default: '<?php echo esc_js(__('Reset Hidden Notices For All', 'disable-admin-notices-individually')); ?>'
                        });
                    });

                    // Handle single notice restore (updated for table structure)
                    $(document).on('click', '.dani-restore-single-notice', function () {
                        var $btn = $(this);
                        var noticeId = $btn.data('notice-id');
                        var target = $btn.data('target');
                        var $noticeRow = $btn.closest('.dani-notice-row, .dani-notice-item'); // Support both table and old structure

                        if (!noticeId) {
                            return;
                        }

                        // Store original button HTML
                        var originalHtml = $btn.html();

                        // Disable button and show loading
                        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear;"></span> <?php echo esc_js(__('Restoring...', 'disable-admin-notices-individually')); ?>');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'dani_restore_single_notice',
                                nonce: '<?php echo esc_js($nonce); ?>',
                                notice_id: noticeId,
                                action_type: target
                            }
                        }).done(function (response) {
                            if (response && response.success) {
                                // Show success message
                                var msg = response.data && response.data.message ? response.data.message : '<?php echo esc_js(__('Notice restored.', 'disable-admin-notices-individually')); ?>';
                                $('#dani-reset-message')
                                    .html('<div class="notice notice-success inline"><p>' + msg + '</p></div>')
                                    .show();

                                // Add removing class and animate out
                                $noticeRow.addClass('removing');

                                // Remove the notice row/item after animation
                                setTimeout(function () {
                                    $noticeRow.remove();

                                    // Check if table is now empty
                                    var $table = $noticeRow.closest('.dani-hidden-notices-table');
                                    if ($table.length && $table.find('tbody tr').length === 0) {
                                        $table.closest('.dani-hidden-notices-details').hide();
                                    }
                                }, 400);

                                // Update counters
                                if (target === 'global' && response.data && typeof response.data.hidden_global_count !== 'undefined') {
                                    var $desc = $('#dani-reset-notices-all').closest('.dani-reset-group').find('.description');
                                    <?php /* translators: %d is the number of notices hidden for all users */ ?>
                                    $desc.text('<?php echo esc_js(__('Currently %d notices are hidden for all users. Click to restore them.', 'disable-admin-notices-individually')); ?>'
                                        .replace('%d', response.data.hidden_global_count));

                                    // Hide details if no more notices
                                    if (response.data.hidden_global_count === 0) {
                                        setTimeout(function () {
                                            $('#dani-reset-notices-all').closest('.dani-reset-group').find('.dani-hidden-notices-details').fadeOut();
                                        }, 500);
                                    }
                                }

                                if (target === 'user' && response.data && typeof response.data.hidden_user_count !== 'undefined') {
                                    var $desc = $('#dani-reset-notices-user').closest('.dani-reset-group').find('.description');
                                    <?php /* translators: %d is the number of notices hidden for the current user */ ?>
                                    $desc.text('<?php echo esc_js(__('Currently %d notices are hidden for your account. Click to show them again.', 'disable-admin-notices-individually')); ?>'
                                        .replace('%d', response.data.hidden_user_count));

                                    // Hide details if no more notices
                                    if (response.data.hidden_user_count === 0) {
                                        setTimeout(function () {
                                            $('#dani-reset-notices-user').closest('.dani-reset-group').find('.dani-hidden-notices-details').fadeOut();
                                        }, 500);
                                    }
                                }

                                // Auto-hide message after 3 seconds
                                setTimeout(function () {
                                    $('#dani-reset-message').fadeOut();
                                }, 3000);
                            } else {
                                var msg = (response && response.data) ? response.data : '<?php echo esc_js(__('Failed to restore notice.', 'disable-admin-notices-individually')); ?>';
                                $('#dani-reset-message').html('<div class="notice notice-error inline"><p>' + msg + '</p></div>').show();

                                // Re-enable button
                                $btn.prop('disabled', false).html(originalHtml);
                            }
                        }).fail(function () {
                            $('#dani-reset-message')
                                .html('<div class="notice notice-error inline"><p><?php echo esc_js(__('Network error occurred. Please try again.', 'disable-admin-notices-individually')); ?></p></div>')
                                .show();

                            // Re-enable button
                            $btn.prop('disabled', false).html(originalHtml);
                        });
                    });

                    // Add rotation animation for loading spinner
                    $('<style>@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }</style>').appendTo('head');
                });
            </script>
            <?php
        }

        public static function admin_page()
        {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Disable Admin Notices Individually', 'disable-admin-notices-individually'); ?></h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('dani_settings');
                    do_settings_sections('dani_settings');
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }

        public static function debug_field_callback()
        {
            $options_instance = Options::instance();
            $debug = $options_instance->get('debug', false);

            // Try to discover the latest log and its size
            $info = self::get_latest_log_info();
            $has_log = $info && !empty($info['size']);
            $size_h = $has_log ? size_format($info['size']) : '';
            $download_url = wp_nonce_url(admin_url('admin-post.php?action=dani_download_log'), 'dani_download_log');
            ?>
            <label>
                <input type="checkbox" name="dani_settings[debug]" value="1" <?php checked($debug, true); ?>>
                <?php esc_html_e('Enable debug logging of notice processing (for troubleshooting).', 'disable-admin-notices-individually'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('When enabled, the plugin writes diagnostic information and hidden notice actions to log files in the uploads/dani-logs directory.', 'disable-admin-notices-individually'); ?>
                <?php if ($debug): ?>
                    <?php if ($has_log): ?>
                        <br>
                        <a href="<?php echo esc_url($download_url); ?>"><?php esc_html_e('Download latest log', 'disable-admin-notices-individually'); ?></a>
                        (<?php echo esc_html($size_h); ?>)
                    <?php else: ?>
                        <br><?php esc_html_e('No log file yet. It will appear after the next action is logged.', 'disable-admin-notices-individually'); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
            <?php
        }

        private static function get_latest_log_info()
        {
            $upload_dir = wp_upload_dir();
            $dir = trailingslashit($upload_dir['basedir']) . 'dani-logs/';
            if (!is_dir($dir)) {
                return null;
            }
            $files = glob($dir . 'dani-debug-*.log');
            if (!$files) {
                return null;
            }
            // sort by mtime desc
            usort($files, function ($a, $b) {
                return filemtime($b) <=> filemtime($a);
            });
            $file = $files[0];
            return [
                    'path' => $file,
                    'size' => @filesize($file),
            ];
        }

        public static function download_log()
        {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('Insufficient permissions.', 'disable-admin-notices-individually'));
            }
            check_admin_referer('dani_download_log');
            $info = self::get_latest_log_info();
            if (!$info || empty($info['path']) || !file_exists($info['path'])) {
                wp_die(esc_html__('Log file not found.', 'disable-admin-notices-individually'));
            }
            $path = $info['path'];
            $filename = basename($path);

            // Use WP_Filesystem instead of direct file operations
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }

            if (!$wp_filesystem || !$wp_filesystem->exists($path)) {
                wp_die(esc_html__('Log file not accessible.', 'disable-admin-notices-individually'));
            }

            $content = $wp_filesystem->get_contents($path);
            if ($content === false) {
                wp_die(esc_html__('Failed to read log file.', 'disable-admin-notices-individually'));
            }

            header('Content-Description: File Transfer');
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Content-Length: ' . strlen($content));
            header('Pragma: public');
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- File content for download, not HTML output
            echo $content;
            exit;
        }

        public static function sanitize_settings($input)
        {
            $options_instance = Options::instance();
            $sanitized = [];

            if (isset($input['mode']) && in_array($input['mode'], ['show_all', 'individual', 'hide_all'], true)) {
                $sanitized['mode'] = $input['mode'];
            } else {
                $sanitized['mode'] = 'individual';
            }

            // Сохраняем текущий список глобально скрытых уведомлений
            $sanitized['global_hidden_notices'] = $options_instance->sanitize_notice_data($input['global_hidden_notices']);

            // Флаг отладки
            $sanitized['debug'] = !empty($input['debug']) ? true : false;

            return $sanitized;
        }
    }
}