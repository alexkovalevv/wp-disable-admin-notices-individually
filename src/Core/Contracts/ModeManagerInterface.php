<?php

namespace DANI\Core\Contracts;

/**
 * Interface for notice mode management
 * 
 * @package DANI\Core\Contracts
 * @since 1.0.0
 */
interface ModeManagerInterface
{
    /**
     * Initialize notice handling based on current mode
     * 
     * @return void
     */
    public function init_notice_handling(): void;

    /**
     * Hide all admin notices
     * 
     * @return void
     */
    public function hide_all_notices(): void;

    /**
     * Clear all notice hooks from WordPress
     * 
     * @return void
     */
    public function clear_notices_hooks(): void;

    /**
     * Get current plugin mode
     * 
     * @return string Current mode (show_all, individual, hide_all)
     */
    public function get_current_mode(): string;
}