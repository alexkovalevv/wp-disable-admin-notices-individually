<?php

namespace DANI\Core\Contracts;

/**
 * Interface for notice rendering operations
 * 
 * @package DANI\Core\Contracts
 * @since 1.0.0
 */
interface NoticeRendererInterface
{
    /**
     * Print captured notices to the screen
     * 
     * @param string|null $hook_name The hook name to print notices for
     * @return void
     */
    public function print_notices($hook_name = null): void;

    /**
     * Render a notice with hide button
     * 
     * @param array $notice Notice data array
     * @return void
     */
    public function render_notice_with_hide_button(array $notice): void;

    /**
     * Prepare notice markup for display
     * 
     * @param string $html Raw notice HTML
     * @return string Prepared HTML
     */
    public function prepare_notice_markup(string $html): string;

    /**
     * Add positioning and buttons to notice HTML
     * 
     * @param string $opening_tag Opening HTML tag
     * @param string $buttons_html Button HTML to add
     * @return string Modified opening tag
     */
    public function add_positioning_and_buttons(string $opening_tag, string $buttons_html): string;
}