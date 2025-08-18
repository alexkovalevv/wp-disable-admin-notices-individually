<?php

namespace DANI\Core;

use DANI\Core\Contracts\NoticeRendererInterface;

/**
 * Handles notice rendering and HTML output
 * 
 * @package DANI\Core
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
        global $dani_captured_notices_by_hook;

        if (empty($dani_captured_notices_by_hook)) {
            return;
        }

        // Use provided hook name or fall back to current_filter()
        $current_hook = $hook_name ?: current_filter();
        $notices = $dani_captured_notices_by_hook[$current_hook] ?? [];

        if (empty($notices)) {
            return;
        }

        // Подключаем ассеты один раз
        static $dani_assets_enqueued = false;
        if (!$dani_assets_enqueued) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('dani-admin', DANI_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], DANI_VERSION, true);
            wp_localize_script('dani-admin', 'dani_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dani_nonce'),
                'hide_me_text' => __('Hide for me', 'disable-admin-notices-individually'),
                'hide_all_text' => __('Hide for all', 'disable-admin-notices-individually'),
            ]);
            wp_enqueue_style('dani-admin', DANI_PLUGIN_URL . 'assets/css/admin.css', [], DANI_VERSION);
            $dani_assets_enqueued = true;
        }

        foreach ($notices as $notice) {
            $this->render_notice_with_hide_button($notice);
        }

        // Clear the notices after printing to avoid duplicates
        unset($dani_captured_notices_by_hook[$current_hook]);
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
        echo wp_kses_post($modified_content);
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

        // Создаем контейнер для кнопок
        $button_group = '<div class="dani-buttons-group">';

        // Скрыть для меня — доступно всем авторизованным
        $button_group .= sprintf(
            '<a href="#" class="button button-small dani-hide-notice dani-hide-for-user" data-target="user" data-notice-id="%s" title="%s">
                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                %s
            </a>',
            esc_attr($notice_id),
            esc_attr__('Hide this notice only for me', 'disable-admin-notices-individually'),
            esc_html__('Hide for me', 'disable-admin-notices-individually')
        );

        // Скрыть для всех — только для тех, у кого есть права админа
        if (current_user_can('manage_options')) {
            $button_group .= sprintf(
                '<a href="#" class="button button-small button-primary dani-hide-notice dani-hide-for-all" data-target="all" data-notice-id="%s" title="%s">
                    <span class="dashicons dashicons-hidden" aria-hidden="true"></span>
                    %s
                </a>',
                esc_attr($notice_id),
                esc_attr__('Hide this notice for all users', 'disable-admin-notices-individually'),
                esc_html__('Hide for all', 'disable-admin-notices-individually')
            );
        }

        $button_group .= '</div>';

        // Добавляем информацию о плагине-источнике уведомления
        $plugin_info = sprintf(
            '<div class="dani-plugin-info">
                <small>
                    %s <strong>%s</strong>. %s <a href="%s">%s</a>
                </small>
            </div>',
            esc_html__('Notice from', 'disable-admin-notices-individually'),
            esc_html($source_plugin),
            esc_html__('Hide controls by', 'disable-admin-notices-individually'),
            esc_url(admin_url('options-general.php?page=disable-admin-notices-individually')),
            esc_html__('Disable Admin Notices Individually', 'disable-admin-notices-individually')
        );

        // Объединяем кнопки и информацию в один контейнер
        $buttons[] = $button_group . $plugin_info;

        return '<div class="dani-hide-button-wrapper">' . implode(' ', $buttons) . '</div>';
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

        // Попытка встроить кнопки в существующий контейнер уведомления
        $processed = false;

        // 1. Ищем div с классом notice (самый распространенный случай)
        if (!$processed && preg_match('/<div([^>]*class="[^"]*notice[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 2. Ищем div с классом updated (WooCommerce и другие)
        if (!$processed && preg_match('/<div([^>]*class="[^"]*updated[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 3. Ищем div с классом error
        if (!$processed && preg_match('/<div([^>]*class="[^"]*error[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 4. Ищем div с классом settings-error
        if (!$processed && preg_match('/<div([^>]*class="[^"]*settings-error[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 5. Ищем div с классом woocommerce-message
        if (!$processed && preg_match('/<div([^>]*class="[^"]*woocommerce-message[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 6. Ищем div с id (как запасной вариант для TGMPA и других)
        if (!$processed && preg_match('/<div([^>]*id="[^"]*"[^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 7. Ищем любой первый div (самый общий случай)
        if (!$processed && preg_match('/<div([^>]*)>/i', $content, $matches)) {
            $opening_tag = $matches[0];
            $new_opening_tag = $this->add_positioning_and_buttons($opening_tag, $buttons_html);
            $content = str_replace($opening_tag, $new_opening_tag, $content);
            $processed = true;
        }

        // 8. Если ничего не найдено, выводим как есть без кнопок
        // WordPress сам разберется с перемещением контента
        if (!$processed) {
            return $content;
        }

        return $content;
    }

}