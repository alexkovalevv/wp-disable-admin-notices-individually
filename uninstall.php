<?php
/**
 * Uninstall script for Disable Admin Notices Individually
 *
 * This file is executed when the plugin is deleted from WordPress.
 * It removes all plugin data from the database.
 *
 * @package DisableAdminNoticesIndividually
 * @version 1.0.0
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check - make sure this is a legitimate uninstall
if (!current_user_can('activate_plugins')) {
    return;
}

// Check if we're on the correct plugin
if (__FILE__ != WP_UNINSTALL_PLUGIN) {
    return;
}

/**
 * Remove plugin options from database
 */
function dani_cleanup_database()
{
    // Remove main plugin settings
    delete_option('dani_settings');

    // Remove any transients that might have been created
    delete_transient('dani_notices_cache');

    // For multisite installations, remove options from all sites
    if (is_multisite()) {
        // Get all sites using WordPress API
        $sites = get_sites(['number' => 0]);

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            // Remove options for this site
            delete_option('dani_settings');
            delete_transient('dani_notices_cache');

            restore_current_blog();
        }
    }
}

/**
 * Remove user meta data
 */
function dani_cleanup_user_meta()
{
    // Remove user meta for all users using WordPress API
    // Get all users who have the meta key
    $users = get_users([
        'meta_key' => 'user_hidden_notices',
        'fields' => 'ID'
    ]);

    foreach ($users as $user_id) {
        delete_user_meta($user_id, 'user_hidden_notices');
    }
}

/**
 * Clean up any scheduled events
 */
function dani_cleanup_scheduled_events()
{
    // Remove any scheduled cron events
    // (Currently the plugin doesn't use cron, but this is for future compatibility)
    wp_clear_scheduled_hook('dani_cleanup_old_notices');
}

/**
 * Log uninstall for debugging (only in debug mode)
 */

// Execute cleanup functions
dani_cleanup_database();
dani_cleanup_user_meta();
dani_cleanup_scheduled_events();

// Clear any object cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}