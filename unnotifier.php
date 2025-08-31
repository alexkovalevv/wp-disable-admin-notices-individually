<?php
/**
 * Plugin Name: Unnotifier — disable admin notices individually
 * Plugin URI: https://wordpress.org/plugins/unnotifier/
 * Description: Unnotifier disable admin notices individually with options to hide all notices, hide selected notices, or show all notices. Each notice gets a "hide forever" button for individual control.
 * Version: 1.0.0
 * Author: Alex Kovalev
 * Author URI: https://alexkovalev.pro
 * Text Domain: unnotifier
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
use DANI\Admin\Settings;
use DANI\Data\Options;

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DANI_VERSION', '1.0.1');
define('DANI_PLUGIN_FILE', __FILE__);
define('DANI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DANI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DANI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// PSR-4 Autoloader for DANI namespace
spl_autoload_register(function ($class) {
    // Only handle classes in DANI namespace
    if (strpos($class, 'DANI\\') !== 0) {
        return;
    }

    // Convert namespace to file path according to PSR-4
    $class_path = str_replace('DANI\\', '', $class);
    $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_path);

    // Build the full file path
    $file = DANI_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $class_path . '.php';

    // Load the file if it exists
    if (file_exists($file)) {
        require_once $file;
    }
});

add_action('init', function () {
    load_textdomain('unnotifier', DANI_PLUGIN_DIR . 'languages/' . get_locale() . '.mo');

    Options::instance();
    Settings::init();
});

\DANI\Core\Notices::instance();

// Wrapper functions for activation/deactivation hooks
if (!function_exists('dani_activate_hook')) {
    function dani_activate_hook()
    {
        Options::instance()->set_defaults();
    }
}
if (!function_exists('dani_deactivate_hook')) {
    function dani_deactivate_hook()
    {

    }
}
register_activation_hook(__FILE__, 'dani_activate_hook');
register_deactivation_hook(__FILE__, 'dani_deactivate_hook');
