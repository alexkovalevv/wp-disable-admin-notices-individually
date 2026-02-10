<?php

namespace UNNO\Core;

use UNNO\Core\Contracts\NoticeRendererInterface;

/**
 * Handles notice rendering and HTML output
 * 
 * @package UNNO\Core
 * @since 1.0.0
 */
class NoticeRenderer implements NoticeRendererInterface
{
    /**
     * Print captured notices to the screen
     * 
     * @param string|null $hook_name The hook name to print notices for
     * @return void
     */
    public function print_notices($hook_name = null): void
    {
        global $unno_captured_notices_by_hook;

        if (empty($unno_captured_notices_by_hook)) {
            return;
        }

        // Use provided hook name or fall back to current_filter()
        $current_hook = $hook_name ?: current_filter();
        $notices = $unno_captured_notices_by_hook[$current_hook] ?? [];

        if (empty($notices)) {
            return;
        }

        // Ensure assets are enqueued only once
        static $unn_assets_enqueued = false;
        if (!$unn_assets_enqueued) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('unno-admin', UNNO_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], UNNO_VERSION, true);
            wp_localize_script('unno-admin', 'unno_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('unno_ajax_nonce'),
                'hide_text' => __('Hide notice', 'unnotifier'),
                'hide_me_text' => __('Hide for me', 'unnotifier'),
                'hide_all_text' => __('Hide for all', 'unnotifier'),
                'restoring_text' => __('Restoring...', 'unnotifier'),
                'restore_error_text' => __('Failed to restore notice. Please try again.', 'unnotifier'),
                'restore_success_text' => __('Notice restored successfully.', 'unnotifier'),
            ]);
            wp_enqueue_style('unno-admin', UNNO_PLUGIN_URL . 'assets/css/admin.css', [], UNNO_VERSION);
            $unn_assets_enqueued = true;
        }

        foreach ($notices as $notice) {
            $this->render_notice_with_hide_button($notice);
        }

        // Clear the notices after printing to avoid duplicates
        unset($unno_captured_notices_by_hook[$current_hook]);
    }

    /**
     * Render a notice with hide button
     * 
     * @param array $notice Notice data array
     * @return void
     */
    public function render_notice_with_hide_button(array $notice): void
    {
        $notice_id = $notice['id'] ?? '';
        $content = $notice['content'] ?? '';
        $source_plugin = $notice['source_plugin'] ?? 'Unknown Plugin';

        if (empty($notice_id) || empty($content)) {
            return;
        }

        $buttons_html = $this->generate_hide_buttons_html($notice_id, $source_plugin);
        $modified_content = $this->inject_buttons_into_notice($content, $buttons_html);

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $modified_content;
    }

    /**
     * Prepare notice markup for display
     * 
     * @param string $html Raw notice HTML
     * @return string Prepared HTML
     */
    public function prepare_notice_markup(string $html): string
    {
        return trim($html);
    }

    /**
     * Add positioning and buttons to notice HTML
     * 
     * @param string $opening_tag Opening HTML tag
     * @param string $buttons_html Button HTML to add
     * @return string Modified opening tag
     */
    public function add_positioning_and_buttons(string $opening_tag, string $buttons_html): string
    {
        if (strpos($opening_tag, 'style=') !== false) {
            $opening_tag = preg_replace('/style="([^"]*)"/', 'style="$1 position: relative;"', $opening_tag);
        } else {
            $opening_tag = str_replace('>', ' style="position: relative;">', $opening_tag);
        }

        return $opening_tag . $buttons_html;
    }

    /**
     * Generate HTML for hide buttons using original markup structure
     * 
     * @param string $notice_id Notice identifier
     * @param string $source_plugin Source plugin name
     * @return string HTML for buttons
     */
    private function generate_hide_buttons_html(string $notice_id, string $source_plugin = 'Unknown Plugin'): string
    {
        $buttons = [];

        // Prepare the action buttons container
        $button_group = '<div class="unno-buttons-group">';

        // Hide for me — available to any authenticated user
        $button_group .= sprintf(
            '<a href="#" class="button button-small unno-hide-notice unno-hide-for-user" data-target="user" data-notice-id="%s" title="%s">
                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                %s
            </a>',
            esc_attr($notice_id),
            esc_attr__('Hide this notice only for me', 'unnotifier'),
            esc_html__('Hide for me', 'unnotifier')
        );

        // Hide for everyone — only for users with administrative capability
        if (current_user_can('manage_options')) {
            $button_group .= sprintf(
                '<a href="#" class="button button-small button-primary unno-hide-notice unno-hide-for-all" data-target="all" data-notice-id="%s" title="%s">
                    <span class="dashicons dashicons-hidden" aria-hidden="true"></span>
                    %s
                </a>',
                esc_attr($notice_id),
                esc_attr__('Hide this notice for all users', 'unnotifier'),
                esc_html__('Hide for all', 'unnotifier')
            );
        }

        $button_group .= '</div>';

        // Append information about the plugin that produced the notice
        $plugin_info = sprintf(
            '<div class="unno-plugin-info">
                <small>
                    %s <strong>%s</strong>. %s <a href="%s">%s</a>
                </small>
            </div>',
            esc_html__('Notice from', 'unnotifier'),
            esc_html($source_plugin),
            esc_html__('Hide controls by', 'unnotifier'),
            esc_url(admin_url('options-general.php?page=unnotifier')),
            esc_html__('Disable Admin Notices Individually', 'unnotifier')
        );

        // Combine buttons and info into a single wrapper
        $buttons[] = $button_group . $plugin_info;

        return '<div class="unno-hide-button-wrapper">' . implode(' ', $buttons) . '</div>';
    }

    /**
     * Inject buttons into notice content using original complex logic
     * 
     * @param string $content Original notice content
     * @param string $buttons_html Buttons HTML to inject
     * @return string Modified content with buttons
     */
    private function inject_buttons_into_notice(string $content, string $buttons_html): string
    {
        $trimmed = trim($content);

        // Don't try to embed buttons if this is not HTML
        if ($trimmed === '' || $trimmed === wp_strip_all_tags($trimmed)) {
            return $content;
        }

        // Try to inject the buttons into an existing notice container
        $processed = false;

        // 1. Locate a div with the notice class (most common scenario)
        if (!$processed && preg_match('/<div([^>]*class="[^"]*notice[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 2. Look for a div with the updated class (WooCommerce and others)
        if (!$processed && preg_match('/<div([^>]*class="[^"]*updated[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 3. Look for a div with the error class
        if (!$processed && preg_match('/<div([^>]*class="[^"]*error[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 4. Look for a div with the settings-error class
        if (!$processed && preg_match('/<div([^>]*class="[^"]*settings-error[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 5. Look for a div with the woocommerce-message class
        if (!$processed && preg_match('/<div([^>]*class="[^"]*woocommerce-message[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 6. As a fallback, look for a div with an ID (covers TGMPA and others)
        if (!$processed && preg_match('/<div([^>]*id="[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 7. As a last resort, target the first available div
        if (!$processed && preg_match('/<div([^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 8. If nothing fits, output the notice as-is without injecting buttons
        // WordPress will handle positioning of the content on its own
        if (!$processed) {
            return $content;
        }

        return $content;
    }

}