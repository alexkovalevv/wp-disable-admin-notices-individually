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
            register_setting('unno_settings', Options::OPTION_KEY, [__CLASS__, 'sanitize_settings']);

            add_settings_section(
                    'unn_main_section',
                    __('Notice Display Settings', 'unnotifier'),
                    [GeneralSettingsPage::class, 'section_callback'],
                    'unno_settings'
            );

            add_settings_field(
                    'mode',
                    __('Notice Mode', 'unnotifier'),
                    [GeneralSettingsPage::class, 'mode_field_callback'],
                    'unno_settings',
                    'unn_main_section'
            );

            add_settings_field(
                    'debug',
                    __('Debug mode', 'unnotifier'),
                    [GeneralSettingsPage::class, 'debug_field_callback'],
                    'unno_settings',
                    'unn_main_section'
            );

            add_settings_field(
                    'show_plugin_names',
                    __('Show plugin names in notices?', 'unnotifier'),
                    [GeneralSettingsPage::class, 'show_plugin_names_field_callback'],
                    'unno_settings',
                    'unn_main_section'
            );

            add_settings_field(
                    'show_notices_in_adminbar',
                    __('Show hidden notices in admin bar', 'unnotifier'),
                    [GeneralSettingsPage::class, 'show_notices_in_adminbar_field_callback'],
                    'unno_settings',
                    'unn_main_section'
            );
        }

        public static function admin_page()
        {
            // Load page classes
            require_once UNNO_PLUGIN_DIR . 'src/Admin/GeneralSettingsPage.php';
            require_once UNNO_PLUGIN_DIR . 'src/Admin/HiddenNoticesPage.php';
            
            // Get active tab
            $active_tab = sanitize_text_field($_GET['tab'] ?? 'general');
            if (!in_array($active_tab, ['general', 'hidden'], true)) {
                $active_tab = 'general';
            }
            
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Disable Admin Notices Individually', 'unnotifier'); ?></h1>
                
                <?php self::render_tabs($active_tab); ?>
                
                <div class="unno-settings-content">
                    <?php
                    if ($active_tab === 'hidden') {
                        $hidden_notices_page = new HiddenNoticesPage();
                        $hidden_notices_page->render();
                    } else {
                        $general_settings_page = new GeneralSettingsPage();
                        $general_settings_page->render();
                    }
                    ?>
                </div>
            </div>
            <?php
        }

        private static function render_tabs(string $active_tab): void {
            $tabs = [
                'general' => __('General Settings', 'unnotifier'),
                'hidden' => __('Hidden Notices', 'unnotifier'),
            ];
            
            $base_url = admin_url('options-general.php?page=unnotifier');
            
            ?>
            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_slug => $tab_label): ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $tab_slug, $base_url)); ?>" 
                       class="nav-tab <?php echo $active_tab === $tab_slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php
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
            $current_options = $options_instance->get_all();
            $sanitized = [];

            if (isset($input['mode']) && in_array($input['mode'], ['show_all', 'individual', 'hide_all'], true)) {
                $sanitized['mode'] = $input['mode'];
            } else {
                $sanitized['mode'] = 'individual';
            }

            // Preserve the existing list of globally hidden notices
            // When the form omits the field, reuse the existing configuration
            if (isset($input['global_hidden_notices']) && is_array($input['global_hidden_notices'])) {
                $sanitized['global_hidden_notices'] = $options_instance->sanitize_notice_data($input['global_hidden_notices']);
            } else {
                // Keep the previously stored hidden notices
                $sanitized['global_hidden_notices'] = isset($current_options['global_hidden_notices']) ? $current_options['global_hidden_notices'] : [];
            }

            // Debug flag
            $sanitized['debug'] = !empty($input['debug']) ? true : false;

            // Toggle for showing plugin names
            $sanitized['show_plugin_names'] = !empty($input['show_plugin_names']) ? true : false;

            // Toggle for showing notices in the admin bar
            $sanitized['show_notices_in_adminbar'] = !empty($input['show_notices_in_adminbar']) ? true : false;

            return $sanitized;
        }
    }
}