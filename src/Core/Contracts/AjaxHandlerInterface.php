<?php

namespace DANI\Core\Contracts;

/**
 * Interface for AJAX operations handling
 * 
 * @package DANI\Core\Contracts
 * @since 1.0.0
 */
interface AjaxHandlerInterface
{
    /**
     * Handle AJAX request to hide a notice
     * 
     * @return void
     */
    public function ajax_hide_notice(): void;

    /**
     * Handle AJAX request to reset hidden notices
     * 
     * @return void
     */
    public function ajax_reset_notices(): void;

    /**
     * Update user's hidden notices list
     * 
     * @param array $data Array of hidden notice IDs
     * @return bool True on success
     */
    public function update_user_hidden_notices(array $data): bool;
}