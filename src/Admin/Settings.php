<?php

namespace UNNO\Admin;

/**
 * Settings and configuration for Disable Admin Notices Individually
 */
if (!defined('ABSPATH')) {
    exit;
}

use UNNO\Data\Options;

if (!class_exists('UNNO\Admin\Settings')) {
    class Settings
    {
        public static function init()
        {
            if (is_admin()) {
                add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
                add_action('admin_init', [__CLASS__, 'admin_init']);
                add_action('admin_post_unn_download_log', [__CLASS__, 'download_log']);
            }
        }

        public static function add_admin_menu()
        {
            add_options_page(
                    __('Disable Admin Notices', 'unnotifier'),
                    __('Unnotifier', 'unnotifier'),
                    'manage_options',
                    'unnotifier',
                    [__CLASS__, 'admin_page']
            );
        }

        public static function admin_init()
        {
            register_setting('unno_settings', 'unno_settings', [__CLASS__, 'sanitize_settings']);

            add_settings_section(
                    'unn_main_section',
                    __('Notice Display Settings', 'unnotifier'),
                    [__CLASS__, 'section_callback'],
                    'unno_settings'
            );

            add_settings_field(
                    'mode',
                    __('Notice Mode', 'unnotifier'),
                    [__CLASS__, 'mode_field_callback'],
                    'unno_settings',
                    'unn_main_section'
            );

            add_settings_field(
                    'reset_notices',
                    __('Reset Hidden Notices', 'unnotifier'),
                    [__CLASS__, 'reset_field_callback'],
                    'unno_settings',
                    'unn_main_section'
            );

            add_settings_field(
                    'debug',
                    __('Debug mode', 'unnotifier'),
                    [__CLASS__, 'debug_field_callback'],
                    'unno_settings',
                    'unn_main_section'
            );

            add_settings_field(
                    'show_plugin_names',
                    __('Show plugin names in notices?', 'unnotifier'),
                    [__CLASS__, 'show_plugin_names_field_callback'],
                    'unno_settings',
                    'unn_main_section'
            );
        }

        public static function section_callback()
        {
            echo '<p>' . esc_html__('Configure how admin notices are displayed in your WordPress dashboard.', 'unnotifier') . '</p>';
        }

        public static function mode_field_callback()
        {
            $options_instance = Options::instance();
            $mode = $options_instance->get('mode', 'individual');

            ?>
            <fieldset>
                <label>
                    <input type="radio" name="unno_settings[mode]"
                           value="show_all" <?php checked($mode, 'show_all'); ?>>
                    <?php esc_html_e('Show all notifications', 'unnotifier'); ?>
                </label><br>
                <p class="description"><?php esc_html_e('Display all admin notifications normally without any hiding options.', 'unnotifier'); ?></p>

                <label>
                    <input type="radio" name="unno_settings[mode]"
                           value="individual" <?php checked($mode, 'individual'); ?>>
                    <?php esc_html_e('Hide notifications individually', 'unnotifier'); ?>
                </label><br>
                <p class="description"><?php esc_html_e('Show all notifications with a "hide forever" button on each one for individual control.', 'unnotifier'); ?></p>

                <label>
                    <input type="radio" name="unno_settings[mode]"
                           value="hide_all" <?php checked($mode, 'hide_all'); ?>>
                    <?php esc_html_e('Hide all notifications', 'unnotifier'); ?>
                </label>
                <p class="description"><?php esc_html_e('Hide all admin notifications completely.', 'unnotifier'); ?></p>
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

            $nonce = wp_create_nonce('unno_ajax_nonce');
            ?>
            <div class="unno-reset-group">
                <h4><?php esc_html_e('Per-user hidden notices', 'unnotifier'); ?></h4>
                <button type="button" id="unno-reset-notices-user" class="button button-secondary">
                    <?php esc_html_e('Reset My Hidden Notices', 'unnotifier'); ?>
                </button>
                <p class="description">
                    <?php
                    // translators: %d is the number of notices currently hidden for the user
                    printf(esc_html__('Currently %d notices are hidden for your account. Click to show them again.', 'unnotifier'), intval($hidden_user_count)); ?>
                </p>

                <?php if ($hidden_user_count > 0): ?>
                    <details class="unno-hidden-notices-details">
                        <summary><?php esc_html_e('Show hidden notices list', 'unnotifier'); ?></summary>
                        <div class="unno-hidden-notices-table-wrapper">
                            <table class="unno-hidden-notices-table">
                                <thead>
                                <tr>
                                    <th class="unno-plugin-column"><?php esc_html_e('Plugin', 'unnotifier'); ?></th>
                                    <th class="unno-content-column"><?php esc_html_e('Notice Content', 'unnotifier'); ?></th>
                                    <th class="unno-actions-column"><?php esc_html_e('Actions', 'unnotifier'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($hidden_user as $notice_id => $notice_data): ?>
                                    <?php
                                    // Handle both old format (simple array) and new format (associative array with metadata)
                                    if (is_array($notice_data)) {
                                        $source_plugin = $notice_data['source_plugin'] ?? 'Unknown Plugin';
                                        $excerpt = $notice_data['notice_excerpt'] ?? $notice_data['excerpt'] ?? 'Administrative notice';
                                        
                                        // Strip HTML tags from excerpt for display
                                        $excerpt = wp_strip_all_tags($excerpt);
                                        if (empty($excerpt)) {
                                            $excerpt = 'Administrative notice';
                                        }
                                    } else {
                                        // Old format - notice_data is actually notice_id
                                        $notice_id = $notice_data;
                                        $source_plugin = 'Unknown Plugin';
                                        $excerpt = 'Legacy notice - ' . substr($notice_id, 0, 20);
                                    }
                                    ?>
                                    <tr class="unno-notice-row" data-notice-id="<?php echo esc_attr($notice_id); ?>">
                                        <td class="unno-plugin-cell">
                                            <strong><?php echo esc_html($source_plugin); ?></strong>
                                        </td>
                                        <td class="unno-content-cell">
                                        <span class="unno-notice-excerpt" title="<?php echo esc_attr($excerpt); ?>">
                                            <?php echo esc_html(mb_strlen($excerpt) > 80 ? mb_substr($excerpt, 0, 77) . '...' : $excerpt); ?>
                                        </span>
                                        </td>
                                        <td class="unno-actions-cell">
                                            <button type="button" class="button button-small unno-restore-single-notice"
                                                    data-target="user"
                                                    data-notice-id="<?php echo esc_attr($notice_id); ?>">
                                                <span class="dashicons dashicons-undo"></span>
                                                <?php esc_html_e('Restore', 'unnotifier'); ?>
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
            <div class="unno-reset-group">
                <h4><?php esc_html_e('Global hidden notices', 'unnotifier'); ?></h4>
                <button type="button" id="unno-reset-notices-all" class="button button-secondary">
                    <?php esc_html_e('Reset Hidden Notices For All', 'unnotifier'); ?>
                </button>
                <p class="description">
                    <?php
                    // translators: %d is the number of notices currently hidden for all users
                    printf(esc_html__('Currently %d notices are hidden for all users. Click to restore them.', 'unnotifier'), intval($hidden_global_count)); ?>
                </p>

                <?php if ($hidden_global_count > 0): ?>
                    <details class="unno-hidden-notices-details">
                        <summary><?php esc_html_e('Show hidden notices list', 'unnotifier'); ?></summary>
                        <div class="unno-hidden-notices-table-wrapper">
                            <table class="unno-hidden-notices-table">
                                <thead>
                                <tr>
                                    <th class="unno-plugin-column"><?php esc_html_e('Plugin', 'unnotifier'); ?></th>
                                    <th class="unno-content-column"><?php esc_html_e('Notice Content', 'unnotifier'); ?></th>
                                    <th class="unno-user-column"><?php esc_html_e('Hidden By', 'unnotifier'); ?></th>
                                    <th class="unno-actions-column"><?php esc_html_e('Actions', 'unnotifier'); ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($hidden_global as $notice_id => $notice_data): ?>
                                    <?php
                                    // Handle both old format (timestamp only) and new format (associative array with metadata)
                                    if (is_array($notice_data)) {
                                        $source_plugin = $notice_data['source_plugin'] ?? 'Unknown Plugin';
                                        $excerpt = $notice_data['notice_excerpt'] ?? $notice_data['excerpt'] ?? 'Administrative notice';
                                        $hidden_by_user_id = $notice_data['hidden_by_user_id'] ?? null;
                                        
                                        // Strip HTML tags from excerpt for display
                                        $excerpt = wp_strip_all_tags($excerpt);
                                        if (empty($excerpt)) {
                                            $excerpt = 'Administrative notice';
                                        }
                                    } else {
                                        // Old format - notice_data is timestamp
                                        $source_plugin = 'Unknown Plugin';
                                        $excerpt = 'Legacy global notice - ' . substr($notice_id, 0, 20);
                                        $hidden_by_user_id = null;
                                    }

                                    // Get user display name if available
                                    $hidden_by_user = __('Unknown', 'unnotifier');
                                    if ($hidden_by_user_id) {
                                        $user = get_userdata($hidden_by_user_id);
                                        if ($user) {
                                            $hidden_by_user = $user->display_name ?: $user->user_login;
                                        }
                                    }
                                    ?>
                                    <tr class="unno-notice-row" data-notice-id="<?php echo esc_attr($notice_id); ?>">
                                        <td class="unno-plugin-cell">
                                            <strong><?php echo esc_html($source_plugin); ?></strong>
                                        </td>
                                        <td class="unno-content-cell">
                                        <span class="unno-notice-excerpt" title="<?php echo esc_attr($excerpt); ?>">
                                            <?php echo esc_html(mb_strlen($excerpt) > 80 ? mb_substr($excerpt, 0, 77) . '...' : $excerpt); ?>
                                        </span>
                                        </td>
                                        <td class="unno-user-cell">
                                            <span class="unno-hidden-user"><?php echo esc_html($hidden_by_user); ?></span>
                                        </td>
                                        <td class="unno-actions-cell">
                                            <button type="button" class="button button-small unno-restore-single-notice"
                                                    data-target="global"
                                                    data-notice-id="<?php echo esc_attr($notice_id); ?>">
                                                <span class="dashicons dashicons-undo"></span>
                                                <?php esc_html_e('Restore', 'unnotifier'); ?>
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

            <div id="unno-reset-message" style="display:none;"></div>
            <?php
        }

        public static function admin_page()
        {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Disable Admin Notices Individually', 'unnotifier'); ?></h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('unno_settings');
                    do_settings_sections('unno_settings');
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
            $download_url = wp_nonce_url(admin_url('admin-post.php?action=unn_download_log'), 'unn_download_log');
            ?>
            <label>
                <input type="checkbox" name="unno_settings[debug]" value="1" <?php checked($debug, true); ?>>
                <?php esc_html_e('Enable debug logging of notice processing (for troubleshooting).', 'unnotifier'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('When enabled, the plugin writes diagnostic information and hidden notice actions to log files in the uploads/unno-logs directory.', 'unnotifier'); ?>
                <?php if ($debug): ?>
                    <?php if ($has_log): ?>
                        <br>
                        <a href="<?php echo esc_url($download_url); ?>"><?php esc_html_e('Download latest log', 'unnotifier'); ?></a>
                        (<?php echo esc_html($size_h); ?>)
                    <?php else: ?>
                        <br><?php esc_html_e('No log file yet. It will appear after the next action is logged.', 'unnotifier'); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
            <?php
        }

        public static function show_plugin_names_field_callback()
        {
            $options_instance = Options::instance();
            $show_plugin_names = $options_instance->get('show_plugin_names', true);
            ?>
            <label>
                <input type="checkbox" name="unno_settings[show_plugin_names]" value="1" <?php checked($show_plugin_names, true); ?>>
                <?php esc_html_e('Show plugin names in notices (enabled by default).', 'unnotifier'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('When enabled, the plugin will attempt to detect and display the name of the plugin that generated each notice. This helps identify the source of notifications.', 'unnotifier'); ?>
                <br>
                <strong><?php esc_html_e('Note:', 'unnotifier'); ?></strong> <?php esc_html_e('This feature uses debug_backtrace() for plugin detection, which may impact performance on high-traffic sites.', 'unnotifier'); ?>
            </p>
            <?php
        }

        private static function get_latest_log_info()
        {
            $upload_dir = wp_upload_dir();
            $dir = trailingslashit($upload_dir['basedir']) . 'unno-logs/';
            if (!is_dir($dir)) {
                return null;
            }
            $files = glob($dir . 'unno-debug-*.log');
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
                wp_die(esc_html__('Insufficient permissions.', 'unnotifier'));
            }
            check_admin_referer('unn_download_log');
            $info = self::get_latest_log_info();
            if (!$info || empty($info['path']) || !file_exists($info['path'])) {
                wp_die(esc_html__('Log file not found.', 'unnotifier'));
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
                wp_die(esc_html__('Log file not accessible.', 'unnotifier'));
            }

            $content = $wp_filesystem->get_contents($path);
            if ($content === false) {
                wp_die(esc_html__('Failed to read log file.', 'unnotifier'));
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