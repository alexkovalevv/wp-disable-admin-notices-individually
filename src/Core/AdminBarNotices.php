<?php

namespace UNNO\Core;

use UNNO\Data\Options;

/**
 * Admin Bar Notices Panel - displays hidden notices inside the admin bar dropdown
 * 
 * @package UNNO\Core
 * @since 1.2.2
 */
class AdminBarNotices
{
    /**
     * @var Options
     */
    private $options;

    /**
     * Constructor
     * 
     * @param Options $options Options instance
     */
    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * Initialize admin bar panel
     * 
     * @return void
     */
    public function init(): void
    {
        // Bail out if the admin bar option is disabled
        if (!$this->options->get('show_notices_in_adminbar', false)) {
            return;
        }

        if (is_admin() || is_network_admin()) {
            add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 999);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        }
    }

    /**
     * Add admin bar menu with hidden notices
     * 
     * @param \WP_Admin_Bar $wp_admin_bar WordPress admin bar instance
     * @return void
     */
    public function add_admin_bar_menu($wp_admin_bar): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Fetch hidden notices for the current user and globally
        $user_hidden = $this->options->get_user_hidden_notices();
        $global_hidden = $this->options->get_global_hidden_notices();

        // Do not render the menu if there is nothing to show
        if (empty($user_hidden) && empty($global_hidden)) {
            return;
        }

        $total_count = count($user_hidden) + count($global_hidden);

        // Register the primary menu toggle item
        $wp_admin_bar->add_menu([
            'id'     => 'unno-hidden-notices-panel',
            'parent' => 'top-secondary',
            'title'  => sprintf(
                __('Hidden Notices %s', 'unnotifier'),
                '<span class="unno-adminbar-counter">' . $total_count . '</span>'
            ),
            'href'   => false,
            'meta'   => [
                'title' => __('Click to view hidden notices', 'unnotifier')
            ]
        ]);
        
        // Append the settings/reset action row
        $nonce = wp_create_nonce('unno_ajax_nonce');
        $settings_title = '<div class="unno-panel-actions-row">';
        $settings_title .= '<div class="unno-panel-action-left">';
        $settings_title .= '<a href="' . esc_url(admin_url('options-general.php?page=unnotifier')) . '" class="unno-settings-link-item">';
        $settings_title .= '<span class="dashicons dashicons-admin-generic"></span> ';
        $settings_title .= __('Manage Settings', 'unnotifier');
        $settings_title .= '</a>';
        $settings_title .= '</div>';
        $settings_title .= '<div class="unno-panel-action-right">';
        $settings_title .= '<a href="#" class="unno-reset-all-link" data-nonce="' . esc_attr($nonce) . '" ';
        $settings_title .= 'title="' . esc_attr(__('Restore all hidden notices', 'unnotifier')) . '">';
        $settings_title .= '<span class="dashicons dashicons-update"></span> ';
        $settings_title .= __('Reset All', 'unnotifier');
        $settings_title .= '</a>';
        $settings_title .= '</div>';
        $settings_title .= '</div>';
        
        $wp_admin_bar->add_menu([
            'id'     => 'unno-hidden-notices-settings',
            'parent' => 'unno-hidden-notices-panel',
            'title'  => $settings_title,
            'href'   => false,
            'meta'   => [
                'class' => 'unno-settings-row'
            ]
        ]);

        $item_counter = 0;

        // Render user-specific hidden notices
        if (!empty($user_hidden)) {
            $wp_admin_bar->add_menu([
                'id'     => 'unno-hidden-notices-user-group',
                'parent' => 'unno-hidden-notices-panel',
                'title'  => __('Hidden for you', 'unnotifier'),
                'href'   => false,
                'meta'   => [
                    'class' => 'unno-group-title'
                ]
            ]);

            foreach ($user_hidden as $notice_id => $notice_data) {
                $this->add_notice_menu_item($wp_admin_bar, $notice_id, $notice_data, 'user', $item_counter);
                $item_counter++;
            }
        }

        // Render notices hidden for everyone (administrators only)
        if (!empty($global_hidden) && current_user_can('manage_options')) {
            $wp_admin_bar->add_menu([
                'id'     => 'unno-hidden-notices-global-group',
                'parent' => 'unno-hidden-notices-panel',
                'title'  => __('Hidden for all users', 'unnotifier'),
                'href'   => false,
                'meta'   => [
                    'class' => 'unno-group-title'
                ]
            ]);

            foreach ($global_hidden as $notice_id => $notice_data) {
                $this->add_notice_menu_item($wp_admin_bar, $notice_id, $notice_data, 'global', $item_counter);
                $item_counter++;
            }
        }
    }

    /**
     * Add single notice menu item
     * 
     * @param \WP_Admin_Bar $wp_admin_bar WordPress admin bar instance
     * @param string $notice_id Notice ID
     * @param mixed $notice_data Notice data (array or legacy format)
     * @param string $scope Scope: 'user' or 'global'
     * @param int $counter Item counter
     * @return void
     */
    private function add_notice_menu_item($wp_admin_bar, string $notice_id, $notice_data, string $scope, int $counter): void
    {
        // Normalize notice data for consistent output
        if (is_array($notice_data)) {
            $source_plugin = $notice_data['source_plugin'] ?? __('Unknown Plugin', 'unnotifier');
            $excerpt = $notice_data['notice_excerpt'] ?? $notice_data['excerpt'] ?? __('Administrative notice', 'unnotifier');
        } else {
            // Legacy format
            $source_plugin = __('Unknown Plugin', 'unnotifier');
            $excerpt = __('Hidden notice', 'unnotifier');
        }

        // IMPORTANT: strip any HTML, JS or potentially unsafe content
        $excerpt = $this->clean_notice_text($excerpt);
        $source_plugin = sanitize_text_field($source_plugin);

        // Trim the excerpt for readability
        if (mb_strlen($excerpt) > 150) {
            $excerpt = mb_substr($excerpt, 0, 147) . '...';
        }

        // Build a WordPress-like title combining plugin badge and text
        $title = '<div class="unno-notice-header">';
        $title .= '<span class="unno-plugin-badge">' . esc_html($source_plugin) . '</span>';
        $title .= '</div>';
        $title .= '<div class="unno-notice-text">' . esc_html($excerpt) . '</div>';
        
        // Append the restore action link
        $nonce = wp_create_nonce('unno_ajax_nonce');
        $title .= '<div class="unno-panel-restore-line">';
        $title .= '<a href="#" class="unno-panel-restore-link" ';
        $title .= 'data-notice-id="' . esc_attr($notice_id) . '" ';
        $title .= 'data-scope="' . esc_attr($scope) . '" ';
        $title .= 'data-nonce="' . esc_attr($nonce) . '" ';
        $title .= 'title="' . esc_attr(__('Click to restore this notice', 'unnotifier')) . '">';
        $title .= '<span class="dashicons dashicons-visibility"></span> ';
        $title .= __('Restore', 'unnotifier');
        $title .= '</a></div>';

        // Determine CSS class for notice styling
        $notice_class = 'unno-notice-item';
        
        $wp_admin_bar->add_menu([
            'id'     => 'unno-hidden-notice-' . $counter,
            'parent' => 'unno-hidden-notices-panel',
            'title'  => $title,
            'href'   => false,
            'meta'   => [
                'class' => $notice_class
            ]
        ]);
    }

    /**
     * Clean notice text from HTML, JavaScript and potentially dangerous code
     * 
     * @param string $text Raw notice text
     * @return string Cleaned text
     */
    private function clean_notice_text(string $text): string
    {
        // Remove all HTML tags
        $text = wp_strip_all_tags($text);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Remove potential inline JS handlers and script tags
        $text = preg_replace('/on\w+\s*=\s*["\'].*?["\']/i', '', $text);
        $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }

    /**
     * Enqueue CSS and JS assets for admin bar panel
     * 
     * @return void
     */
    public function enqueue_assets(): void
    {
        // Skip enqueuing if the admin bar option is disabled
        if (!$this->options->get('show_notices_in_adminbar', false)) {
            return;
        }

        // CSS for modal dialogs
        wp_enqueue_style(
            'unnotifier-modal-dialog',
            UNNO_PLUGIN_URL . 'assets/css/modal-dialog.css',
            [],
            UNNO_VERSION
        );

        // CSS for the admin bar panel styling
        wp_enqueue_style(
            'unnotifier-adminbar-panel',
            UNNO_PLUGIN_URL . 'assets/css/adminbar-panel.css',
            [],
            UNNO_VERSION
        );

        // JavaScript for modal dialogs (loads first)
        wp_enqueue_script(
            'unnotifier-modal-dialog',
            UNNO_PLUGIN_URL . 'assets/js/modal-dialog.js',
            ['jquery'],
            UNNO_VERSION,
            true
        );

        // JavaScript that powers the admin bar panel interactions
        wp_enqueue_script(
            'unnotifier-adminbar-panel',
            UNNO_PLUGIN_URL . 'assets/js/adminbar-panel.js',
            ['jquery', 'unnotifier-modal-dialog'],
            UNNO_VERSION,
            true
        );

        // Localize script
        wp_localize_script('unnotifier-adminbar-panel', 'unno_adminbar', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('unno_ajax_nonce'),
            'restore_text' => __('Restore notice', 'unnotifier'),
            'error_text' => __('Error restoring notice. Please try again.', 'unnotifier'),
            'reset_title' => __('Reset All Hidden Notices', 'unnotifier'),
            'reset_confirm' => __('Are you sure you want to restore all hidden notices? This will make them visible again in your WordPress dashboard.', 'unnotifier'),
            'reset_button' => __('Yes, Reset All', 'unnotifier'),
            'cancel_button' => __('Cancel', 'unnotifier'),
            'resetting_text' => __('Resetting...', 'unnotifier')
        ]);
    }

    /**
     * Get excerpt from text
     * 
     * @param string $text Full text
     * @param int $length Maximum length
     * @return string Excerpt
     */
    private function get_excerpt(string $text, int $length = 200): string
    {
        $text = wp_strip_all_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) > $length) {
            $text = substr($text, 0, $length - 3) . '...';
        }

        return $text;
    }
}

