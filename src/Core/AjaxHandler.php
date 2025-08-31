<?php

namespace DANI\Core;

use DANI\Core\Contracts\AjaxHandlerInterface;
use DANI\Data\Options;
use DANI\Core\Logger;

/**
 * Handles AJAX requests for notice operations
 *
 * @package DANI\Core
 * @since 1.0.0
 */
class AjaxHandler implements AjaxHandlerInterface
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
     * Handle AJAX request to hide a notice
     *
     * @return void
     */
    public function ajax_hide_notice(): void
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'dani_nonce')) {
            wp_send_json_error(__('Invalid nonce.', 'unnotifier'));
            return;
        }

        $notice_id = sanitize_text_field(wp_unslash($_POST['notice_id'] ?? ''));
        $action_type = sanitize_text_field(wp_unslash($_POST['action_type'] ?? 'user'));

        // Get additional notice metadata
        $notice_metadata = [
            'source_plugin' => sanitize_text_field(wp_unslash($_POST['source_plugin'] ?? 'Unknown Plugin')),
            'content' => wp_kses_post(wp_unslash($_POST['notice_content'] ?? '')),
            'excerpt' => sanitize_text_field(wp_unslash($_POST['notice_excerpt'] ?? '')),
            'hidden_at' => current_time('timestamp'),
            'hidden_by_user_id' => get_current_user_id()
        ];

        if (empty($notice_id)) {
            wp_send_json_error(__('Missing notice ID.', 'unnotifier'));
            return;
        }

        Logger::log('AJAX hide notice request', [
            'notice_id' => $notice_id,
            'action_type' => $action_type,
            'user_id' => get_current_user_id(),
            'source_plugin' => $notice_metadata['source_plugin'],
            'excerpt' => $notice_metadata['excerpt']
        ]);

        if ($action_type === 'all') {
            $this->handle_global_hide($notice_id, $notice_metadata);
        } else {
            $this->handle_user_hide($notice_id, $notice_metadata);
        }
    }

    /**
     * Handle AJAX request to reset hidden notices
     *
     * @return void
     */
    public function ajax_reset_notices(): void
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'dani_nonce')) {
            wp_send_json_error(__('Invalid nonce.', 'unnotifier'));
            return;
        }

        $action_type = sanitize_text_field(wp_unslash($_POST['action_type'] ?? 'user'));

        Logger::log('AJAX reset notices request', [
            'action_type' => $action_type,
            'user_id' => get_current_user_id()
        ]);

        if ($action_type === 'all') {
            $this->handle_global_reset();
        } else {
            $this->handle_user_reset();
        }
    }

    /**
     * Handle AJAX request to restore a single hidden notice
     *
     * @return void
     */
    public function ajax_restore_single_notice(): void
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'dani_nonce')) {
            wp_send_json_error(__('Invalid nonce.', 'unnotifier'));
            return;
        }

        $notice_id = sanitize_text_field(wp_unslash($_POST['notice_id'] ?? ''));
        $action_type = sanitize_text_field(wp_unslash($_POST['action_type'] ?? 'user'));

        if (empty($notice_id)) {
            wp_send_json_error(__('Missing notice ID.', 'unnotifier'));
            return;
        }

        Logger::log('AJAX restore single notice request', [
            'notice_id' => $notice_id,
            'action_type' => $action_type,
            'user_id' => get_current_user_id()
        ]);

        if ($action_type === 'all') {
            $this->handle_single_global_restore($notice_id);
        } else {
            $this->handle_single_user_restore($notice_id);
        }
    }

    /**
     * Update user's hidden notices list
     *
     * @param array $data Array of hidden notice IDs
     * @return bool True on success
     */
    public function update_user_hidden_notices(array $data): bool
    {
        return $this->options->update_user_hidden_notices($data);
    }

    /**
     * Handle hiding notice globally for all users
     *
     * @param string $notice_id Notice identifier
     * @param array $metadata Notice metadata
     * @return void
     */
    private function handle_global_hide(string $notice_id, array $metadata = []): void
    {
        // Check permissions
        if (!current_user_can('manage_options')) {
            Logger::log('Global hide failed: insufficient permissions', ['notice_id' => $notice_id]);
            wp_send_json_error(__('Insufficient permissions to hide notices globally.', 'unnotifier'));
            return;
        }

        // Validate notice ID
        if (empty($notice_id)) {
            wp_send_json_error(__('Invalid notice ID provided.', 'unnotifier'));
            return;
        }

        // Check if notice is already hidden
        $current_notices = $this->options->get_global_hidden_notices();
        if (isset($current_notices[$notice_id])) {
            wp_send_json_success(__('Notice is already hidden globally for all users.', 'unnotifier'));
            return;
        }

        // Try to hide the notice
        $result = $this->options->add_global_hidden_notice($notice_id, $metadata);

        if ($result) {
            Logger::log('Notice hidden globally', ['notice_id' => $notice_id]);
            wp_send_json_success(__('Notice hidden globally for all users.', 'unnotifier'));
        } else {
            // Check for specific error conditions
            $error_message = $this->get_failure_reason($notice_id);
            Logger::log('Global hide failed', [
                'notice_id' => $notice_id,
                'db_error' => $GLOBALS['wpdb']->last_error ?? 'none'
            ]);
            wp_send_json_error($error_message);
        }
    }

    /**
     * Get specific error message for failure
     *
     * @param string $notice_id Notice identifier
     * @return string Error message
     */
    private function get_failure_reason(string $notice_id): string
    {
        // Check database error
        if (!empty($GLOBALS['wpdb']->last_error)) {
            return __('Database error occurred. Please check database connection and permissions.', 'unnotifier');
        }

        // Check if options are accessible
        if ($this->options->get_all() === false) {
            return __('Cannot access plugin settings. Please check database permissions.', 'unnotifier');
        }

        // Generic error
        return __('Failed to hide notice globally. Please try again or contact administrator.', 'unnotifier');
    }

    /**
     * Handle hiding notice for current user only
     *
     * @param string $notice_id Notice identifier
     * @param array $metadata Notice metadata
     * @return void
     */
    private function handle_user_hide(string $notice_id, array $metadata = []): void
    {
        // Check if user is logged in
        $user_id = get_current_user_id();
        if ($user_id === 0) {
            Logger::log('User hide failed: user not logged in', [
                'notice_id' => $notice_id,
                'session_user_id' => $user_id,
                'is_user_logged_in' => is_user_logged_in()
            ]);
            wp_send_json_error(__('User not logged in. Please log in to hide notices.', 'unnotifier'));
            return;
        }

        // Validate notice ID
        if (empty($notice_id) || !is_string($notice_id)) {
            Logger::log('User hide failed: invalid notice ID', [
                'notice_id' => $notice_id,
                'notice_id_type' => gettype($notice_id),
                'user_id' => $user_id
            ]);
            wp_send_json_error(__('Invalid notice ID provided.', 'unnotifier'));
            return;
        }

        // Get current user hidden notices for debugging
        $current_user_notices = $this->options->get_user_hidden_notices($user_id);

        // Check if notice is already hidden for user
        if (isset($current_user_notices[$notice_id])) {
            Logger::log('Notice already hidden for user', [
                'notice_id' => $notice_id,
                'user_id' => $user_id,
                'existing_data' => $current_user_notices[$notice_id]
            ]);
            wp_send_json_success(__('Notice is already hidden for you.', 'unnotifier'));
            return;
        }

        Logger::log('Attempting to hide notice for user', [
            'notice_id' => $notice_id,
            'user_id' => $user_id,
            'metadata' => $metadata,
            'current_user_notices_count' => count($current_user_notices)
        ]);

        // Attempt to add notice to user hidden list
        $result = $this->options->add_user_hidden_notice($notice_id, $metadata, $user_id);

        if ($result) {
            $updated_user_notices = $this->options->get_user_hidden_notices($user_id);
            Logger::log('Successfully added notice to user hidden', [
                'notice_id' => $notice_id,
                'user_id' => $user_id,
                'metadata' => $metadata,
                'previous_count' => count($current_user_notices),
                'new_count' => count($updated_user_notices)
            ]);
            wp_send_json_success(__('Notice hidden for you.', 'unnotifier'));
        } else {
            // Detailed error investigation
            $error_details = [
                'notice_id' => $notice_id,
                'user_id' => $user_id,
                'metadata_provided' => !empty($metadata),
                'metadata_count' => count($metadata),
                'current_user_notices_count' => count($current_user_notices),
                'wp_debug' => defined('WP_DEBUG') ? WP_DEBUG : 'undefined',
                'db_error' => $GLOBALS['wpdb']->last_error ?? 'none'
            ];

            // Check if database connection is working
            if (!empty($GLOBALS['wpdb']->last_error)) {
                $error_details['specific_error'] = 'Database error: ' . $GLOBALS['wpdb']->last_error;
                Logger::log('User hide failed: database error', $error_details);
                wp_send_json_error(__('Database error occurred while hiding notice. Please check database connection.', 'unnotifier'));
                return;
            }

            // Check if user meta is accessible
            $test_meta = get_user_meta($user_id, 'test_meta', true);
            if ($test_meta === false && $GLOBALS['wpdb']->last_error) {
                $error_details['specific_error'] = 'Cannot access user meta data';
                Logger::log('User hide failed: cannot access user meta', $error_details);
                wp_send_json_error(__('Cannot access user settings. Please check database permissions.', 'unnotifier'));
                return;
            }

            // Check if user exists and is valid
            $user_data = get_userdata($user_id);
            if (!$user_data) {
                $error_details['specific_error'] = 'User data not found for user ID: ' . $user_id;
                Logger::log('User hide failed: invalid user', $error_details);
                wp_send_json_error(__('User account not found. Please log in again.', 'unnotifier'));
                return;
            }

            // Generic failure with detailed logging
            $error_details['specific_error'] = 'add_user_hidden_notice returned false for unknown reason';
            $error_details['user_login'] = $user_data->user_login;
            $error_details['user_exists'] = !empty($user_data);
            Logger::log('User hide failed: unknown reason', $error_details);

            wp_send_json_error(__('Failed to hide notice for user. Check error logs for details.', 'unnotifier'));
        }
    }

    /**
     * Handle resetting all global hidden notices
     *
     * @return void
     */
    private function handle_global_reset(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'unnotifier'));
            return;
        }

        if ($this->options->clear_global_hidden_notices()) {
            // Also reset current user's notices for immediate effect
            if (is_user_logged_in()) {
                $this->update_user_hidden_notices([]);
            }

            Logger::log('Reset all global hidden notices');

            // Return updated count for JavaScript to update the UI
            $updated_global_count = count($this->options->get_global_hidden_notices());
            $updated_user_count = count($this->options->get_user_hidden_notices());

            wp_send_json_success([
                'message' => __('All hidden notices have been reset for all users.', 'unnotifier'),
                'hidden_global_count' => $updated_global_count,
                'hidden_user_count' => $updated_user_count
            ]);
        } else {
            wp_send_json_error(__('Failed to reset notices.', 'unnotifier'));
        }
    }

    /**
     * Handle resetting current user's hidden notices
     *
     * @return void
     */
    private function handle_user_reset(): void
    {
        $user_id = get_current_user_id();
        if ($user_id === 0) {
            wp_send_json_error(__('User not logged in.', 'unnotifier'));
            return;
        }

        if ($this->update_user_hidden_notices([])) {
            Logger::log('Reset user hidden notices', ['user_id' => $user_id]);

            // Return updated count for JavaScript to update the UI
            $updated_user_count = count($this->options->get_user_hidden_notices());

            wp_send_json_success([
                'message' => __('Your hidden notices have been reset.', 'unnotifier'),
                'hidden_user_count' => $updated_user_count
            ]);
        } else {
            wp_send_json_error(__('Failed to reset your notices.', 'unnotifier'));
        }
    }

    /**
     * Handle restoring a single global hidden notice
     *
     * @param string $notice_id Notice identifier
     * @return void
     */
    private function handle_single_global_restore(string $notice_id): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'unnotifier'));
            return;
        }

        if ($this->options->remove_global_hidden_notice($notice_id)) {
            Logger::log('Restored single global hidden notice', ['notice_id' => $notice_id]);

            // Return updated counts
            $updated_global_count = count($this->options->get_global_hidden_notices());
            $updated_user_count = count($this->options->get_user_hidden_notices());

            wp_send_json_success([
                // translators: %s is the notice ID that was restored
                'message' => sprintf(__('Notice %s has been restored for all users.', 'unnotifier'), $notice_id),
                'notice_id' => $notice_id,
                'hidden_global_count' => $updated_global_count,
                'hidden_user_count' => $updated_user_count
            ]);
        } else {
            wp_send_json_error(__('Failed to restore notice globally.', 'unnotifier'));
        }
    }

    /**
     * Handle restoring a single user hidden notice
     *
     * @param string $notice_id Notice identifier
     * @return void
     */
    private function handle_single_user_restore(string $notice_id): void
    {
        $user_id = get_current_user_id();
        if ($user_id === 0) {
            wp_send_json_error(__('User not logged in.', 'unnotifier'));
            return;
        }

        $hidden_user = $this->options->get_user_hidden_notices();

        // Check if notice exists in the hidden list (as key in associative array)
        if (isset($hidden_user[$notice_id])) {
            unset($hidden_user[$notice_id]);

            if ($this->update_user_hidden_notices($hidden_user)) {
                Logger::log('Restored single user hidden notice', [
                    'notice_id' => $notice_id,
                    'user_id' => $user_id
                ]);

                // Return updated count
                $updated_user_count = count($this->options->get_user_hidden_notices());

                wp_send_json_success([
                    // translators: %s is the notice ID that was restored
                    'message' => sprintf(__('Notice %s has been restored for you.', 'unnotifier'), $notice_id),
                    'notice_id' => $notice_id,
                    'hidden_user_count' => $updated_user_count
                ]);
            } else {
                wp_send_json_error(__('Failed to restore notice for user.', 'unnotifier'));
            }
        } else {
            // Check for backward compatibility with old format (simple array)
            $key = array_search($notice_id, $hidden_user, true);
            if ($key !== false) {
                unset($hidden_user[$key]);
                $hidden_user = array_values($hidden_user); // Re-index array

                if ($this->update_user_hidden_notices($hidden_user)) {
                    Logger::log('Restored single user hidden notice (legacy format)', [
                        'notice_id' => $notice_id,
                        'user_id' => $user_id
                    ]);

                    // Return updated count
                    $updated_user_count = count($this->options->get_user_hidden_notices());

                    wp_send_json_success([
                        // translators: %s is the notice ID that was restored
                        'message' => sprintf(__('Notice %s has been restored for you.', 'unnotifier'), $notice_id),
                        'notice_id' => $notice_id,
                        'hidden_user_count' => $updated_user_count
                    ]);
                } else {
                    wp_send_json_error(__('Failed to restore notice for user.', 'unnotifier'));
                }
            } else {
                wp_send_json_error(__('Notice not found in hidden list.', 'unnotifier'));
            }
        }
    }
}