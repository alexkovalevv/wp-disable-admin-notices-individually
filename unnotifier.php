<?php
/**
 * Plugin Name: Unnotifier — disable admin notices individually
 * Plugin URI: https://wp-aifactory.com/unnotifier-disable-admin-notices-wordpress-plugin/
 * Description: Unnotifier disable admin notices individually with options to hide all notices, hide selected notices, or show all notices. Each notice gets a "hide forever" button for individual control.
 * Version: 1.2.7
 * Author: Alex Kovalev
 * Author URI: https://wp-aifactory.com
 * Text Domain: unnotifier
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Tested up to PHP: 8.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
use UNNO\Admin\Settings;
use UNNO\Admin\ReviewNoticeManager;
use UNNO\Data\Options;

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('UNNO_VERSION', '1.2.7');
define('UNNO_PLUGIN_FILE', __FILE__);
define('UNNO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UNNO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UNNO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Debug constant for review notice (set to true to force show notice for testing)
if (!defined('UNNO_REVIEW_NOTICE_DEBUG')) {
    define('UNNO_REVIEW_NOTICE_DEBUG', false);
}

// PSR-4 Autoloader for UNNO namespace
spl_autoload_register(function ($class) {
    // Only handle classes in UNNO namespace
    if (strpos($class, 'UNNO\\') !== 0) {
        return;
    }

    // Convert namespace to file path according to PSR-4
    $class_path = str_replace('UNNO\\', '', $class);
    $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_path);

    // Build the full file path
    $file = UNNO_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $class_path . '.php';

    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});

add_action('init', function () {
    load_textdomain('unnotifier', UNNO_PLUGIN_DIR . 'languages/' . get_locale() . '.mo');

    Options::instance();
    Settings::init();
    
    // Initialize review notice manager
    $review_notice_manager = new ReviewNoticeManager();
    $review_notice_manager->init();
});

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', function ($hook) {
    // Load CSS on all admin pages where notices appear
    if (strpos($hook, 'admin') !== false) {
        // Enqueue admin CSS
        wp_enqueue_style(
            'unnotifier-admin',
            UNNO_PLUGIN_URL . 'assets/css/admin.css',
            [],
            UNNO_VERSION
        );
    }

    // Common AJAX localization data
    $ajax_localization = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('unno_ajax_nonce'),
        'hide_text' => __('Hide notice', 'unnotifier'),
        'hide_me_text' => __('Hide for me', 'unnotifier'),
        'hide_all_text' => __('Hide for all', 'unnotifier'),
        'restoring_text' => __('Restoring...', 'unnotifier'),
        'restore_error_text' => __('Failed to restore notice. Please try again.', 'unnotifier'),
        'restore_success_text' => __('Notice restored successfully.', 'unnotifier'),
        'debug' => Options::instance()->get('debug', false)
    ];

    // Load JS on all admin pages where notices appear
    if (strpos($hook, 'admin') !== false) {
        // Enqueue admin JS
        wp_enqueue_script(
            'unnotifier-admin',
            UNNO_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            UNNO_VERSION,
            true
        );

        // Localize script with AJAX data
        wp_localize_script('unnotifier-admin', 'unno_ajax', $ajax_localization);
    }

    // Load settings-specific JS only on settings page
    if ($hook === 'settings_page_unnotifier') {
        wp_enqueue_script(
            'unnotifier-admin-settings',
            UNNO_PLUGIN_URL . 'assets/js/admin-settings.js',
            ['jquery'],
            UNNO_VERSION,
            true
        );

        // Localize settings script with AJAX data
        wp_localize_script('unnotifier-admin-settings', 'unno_ajax', $ajax_localization);
    }
});

\UNNO\Core\Notices::instance();

// Wrapper functions for activation/deactivation hooks
if (!function_exists('unno_activate_hook')) {
    function unno_activate_hook()
    {
        Options::instance()->set_defaults();
        
        // Set activation time for review notice if not already set
        if (!get_option('unno_activation_time')) {
            update_option('unno_activation_time', time());
        }
    }
}
if (!function_exists('unno_deactivate_hook')) {
    function unno_deactivate_hook()
    {

    }
}
register_activation_hook(__FILE__, 'unno_activate_hook');
register_deactivation_hook(__FILE__, 'unno_deactivate_hook');
