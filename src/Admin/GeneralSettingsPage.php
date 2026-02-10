<?php

declare(strict_types=1);

namespace UNNO\Admin;

/**
 * General Settings Page for Unnotifier plugin.
 * 
 * Displays the general settings form with all configuration options.
 * 
 * @package UNNO\Admin
 * @since 1.2.5
 */
if (!defined('ABSPATH')) {
    exit;
}

use UNNO\Data\Options;

class GeneralSettingsPage {
    
    /**
     * Render the general settings page.
     * 
     * @since 1.2.5
     * @return void
     */
    public function render(): void {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('unno_settings');
            do_settings_sections('unno_settings');
            submit_button();
            ?>
        </form>
        <?php
    }
    
    /**
     * Section callback for settings section.
     * 
     * @since 1.2.5
     * @return void
     */
    public static function section_callback(): void {
        echo '<p>' . esc_html__('Configure how admin notices are displayed in your WordPress dashboard.', 'unnotifier') . '</p>';
    }
    
    /**
     * Mode field callback.
     * 
     * @since 1.2.5
     * @return void
     */
    public static function mode_field_callback(): void {
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
    
    /**
     * Debug field callback.
     * 
     * @since 1.2.5
     * @return void
     */
    public static function debug_field_callback(): void {
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
    
    /**
     * Show plugin names field callback.
     * 
     * @since 1.2.5
     * @return void
     */
    public static function show_plugin_names_field_callback(): void {
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
    
    /**
     * Show notices in adminbar field callback.
     * 
     * @since 1.2.5
     * @return void
     */
    public static function show_notices_in_adminbar_field_callback(): void {
        $options_instance = Options::instance();
        $show_notices_in_adminbar = $options_instance->get('show_notices_in_adminbar', false);
        ?>
        <label>
            <input type="checkbox" name="unno_settings[show_notices_in_adminbar]" value="1" <?php checked($show_notices_in_adminbar, true); ?>>
            <?php esc_html_e('Enable admin bar panel with hidden notices', 'unnotifier'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, all hidden notices will be collected and displayed in a dropdown panel in the WordPress admin bar. This allows you to review hidden notices without them cluttering your workspace. Each notice can be restored individually from the admin bar.', 'unnotifier'); ?>
            <br>
            <strong><?php esc_html_e('Benefits:', 'unnotifier'); ?></strong>
            <?php esc_html_e('Quick access to hidden notices, restore notices without going to settings page, see notice counts at a glance.', 'unnotifier'); ?>
        </p>
        <?php
    }
    
    /**
     * Get latest log file info.
     * 
     * @since 1.2.5
     * @return array|null Log file info or null
     */
    private static function get_latest_log_info(): ?array {
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
}

