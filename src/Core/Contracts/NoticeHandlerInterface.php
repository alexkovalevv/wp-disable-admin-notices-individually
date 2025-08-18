<?php

namespace DANI\Core\Contracts;

/**
 * Interface for notice handling operations
 * 
 * @package DANI\Core\Contracts
 * @since 1.0.0
 */
interface NoticeHandlerInterface
{
    /**
     * Capture and process notices from WordPress hooks
     * 
     * @return void
     */
    public function capture_notices(): void;

    /**
     * Check if a notice should be hidden
     * 
     * @param string $notice_id The notice identifier
     * @param array $hidden_user User-specific hidden notices
     * @param array $hidden_global Globally hidden notices
     * @return bool True if notice should be hidden
     */
    public function is_notice_hidden(string $notice_id, array $hidden_user, array $hidden_global): bool;

    /**
     * Generate a unique identifier for a notice
     * 
     * @param string $content Notice content
     * @param mixed $callback Notice callback function
     * @return array Array with two hash parts
     */
    public function generate_notice_pair(string $content, $callback): array;
}