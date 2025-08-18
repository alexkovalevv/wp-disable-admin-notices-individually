<?php

namespace DANI\Data;

/**
 * Centralized options storage/retrieval for Disable Admin Notices Individually
 */
if (!defined('ABSPATH')) {
    exit;
}

use DANI\Core\SingletonTrait;
use DANI\Core\Logger;

if (!class_exists('DANI\Data\Options')) {
    class Options
    {
        use SingletonTrait;

        /**
         * Единственный экземпляр класса
         */
        public const OPTION_KEY = 'dani_settings';
        public const USER_HIDDEN_NOTICES_KEY = 'user_hidden_notices';
        public const GLOBAL_HIDDEN_NOTICES_KEY = 'global_hidden_notices';

        private $defaults = [
            'mode' => 'individual',
            'debug' => false,
            self::GLOBAL_HIDDEN_NOTICES_KEY => [],
        ];

        /**
         * Ensure defaults are present in the option storage.
         */
        public function set_defaults()
        {
            $options = get_option(self::OPTION_KEY, []);

            if (empty($options)) {
                $result = update_option(self::OPTION_KEY, $this->defaults);
                if (!$result) {
                    // Try to add option instead of update
                    $result = add_option(self::OPTION_KEY, $this->defaults);
                }
                Logger::log('Options: Set defaults', ['success' => $result]);
            }
        }

        /**
         * Get full options array merged with defaults.
         */
        public function get_all(): array
        {
            return get_option(self::OPTION_KEY, []);
        }

        /**
         * Set entire options array (preserving unknown keys as-is if needed).
         */
        public function set_all(array $options): bool
        {
            $current = $this->get_all();
            $new = array_merge($current, $options);
            return (bool)update_option(self::OPTION_KEY, $new);
        }

        /**
         * Get a single option by key with default.
         */
        public function get(string $key, $default = null)
        {
            $all = $this->get_all();
            return array_key_exists($key, $all) ? $all[$key] : $default;
        }

        /**
         * Update a single option key.
         */
        public function update(string $key, $value): bool
        {
            $all = $this->get_all();
            $all[$key] = $value;

            $result = update_option(self::OPTION_KEY, $all);

            return (bool)$result;
        }


        /**
         * Get per-user hidden notices for current user or specific user ID.
         */
        public function get_user_hidden_notices($user_id = null): array
        {
            $uid = $user_id ? intval($user_id) : get_current_user_id();
            if ($uid <= 0) {
                return [];
            }
            $data = get_user_meta($uid, self::USER_HIDDEN_NOTICES_KEY, true);
            return is_array($data) ? $data : [];
        }

        /**
         * Add a notice to user hidden notices with metadata.
         */
        public function add_user_hidden_notice(string $notice_key, array $metadata = [], $user_id = null): bool
        {
            $uid = $user_id ? intval($user_id) : get_current_user_id();
            if ($uid <= 0) {
                return false;
            }

            $hidden = $this->get_user_hidden_notices($uid);

            // If metadata is provided, store as associative array with metadata
            if (!empty($metadata)) {
                $notice_data = array_merge([
                    'hidden_at' => current_time('timestamp'),
                    'source_plugin' => 'Unknown Plugin',
                    'excerpt' => '',
                    'content' => ''
                ], $metadata);

                // Санитизируем данные перед сохранением
                $notice_data = $this->sanitize_notice_data($notice_data);

                $hidden[$notice_key] = $notice_data;
            } else {
                // Backward compatibility: if no metadata, store with timestamp only
                if (!isset($hidden[$notice_key])) {
                    $hidden[$notice_key] = ['hidden_at' => current_time('timestamp')];
                }
            }

            return $this->update_user_hidden_notices($hidden, $uid);
        }

        /**
         * Update per-user hidden notices for current user or specific user ID.
         */
        public function update_user_hidden_notices(array $data, $user_id = null): bool
        {
            $uid = $user_id ? intval($user_id) : get_current_user_id();
            if ($uid <= 0) {
                return false;
            }

            // Get current value to check if it's actually changing
            $current = $this->get_user_hidden_notices($uid);

            // If the new data is the same as current, consider it successful
            // This prevents WordPress update_user_meta from returning false when values are identical
            if ($current === $data) {
                return true;
            }

            // Attempt to update the meta value
            $result = update_user_meta($uid, self::USER_HIDDEN_NOTICES_KEY, $data);

            // update_user_meta returns meta_id on success, false on failure
            // For our purposes, we consider it successful if it's not false
            return $result !== false;
        }

        /**
         * Get global hidden notices.
         */
        public function get_global_hidden_notices(): array
        {
            $data = $this->get(self::GLOBAL_HIDDEN_NOTICES_KEY, []);
            return is_array($data) ? $data : [];
        }


        /**
         * Add a notice to global hidden notices with metadata.
         */
        public function add_global_hidden_notice(string $notice_key, array $metadata = []): bool
        {
            $hidden = $this->get_global_hidden_notices();

            // Check if notice already exists and metadata is empty (simple check)
            if (empty($metadata) && isset($hidden[$notice_key])) {
                return true;
            }

            // Prepare notice data
            if (!empty($metadata)) {
                $notice_data = array_merge([
                    'hidden_at' => current_time('timestamp'),
                    'source_plugin' => 'Unknown Plugin',
                    'excerpt' => '',
                    'content' => ''
                ], $metadata);
            } else {
                $notice_data = ['hidden_at' => current_time('timestamp')];
            }

            // Санитизируем данные перед сохранением
            $notice_data = $this->sanitize_notice_data($notice_data);

            $hidden[$notice_key] = $notice_data;

            $result = $this->update(self::GLOBAL_HIDDEN_NOTICES_KEY, $hidden);

            if (!$result) {
                Logger::log('Options: Failed to add global hidden notice', [
                    'notice_key' => $notice_key,
                    'has_metadata' => !empty($metadata),
                    'sanitized_data' => $notice_data
                ]);
            }

            return $result;
        }


        /**
         * Remove a notice from global hidden notices.
         */
        public function remove_global_hidden_notice(string $notice_key): bool
        {
            $hidden = $this->get_global_hidden_notices();

            // Check if notice exists in associative array
            if (isset($hidden[$notice_key])) {
                unset($hidden[$notice_key]);
                return $this->update(self::GLOBAL_HIDDEN_NOTICES_KEY, $hidden);
            }

            return true; // Already doesn't exist
        }


        /**
         * Clear all global hidden notices.
         */
        public function clear_global_hidden_notices(): bool
        {
            return $this->update(self::GLOBAL_HIDDEN_NOTICES_KEY, []);
        }

        /**
         * Add general setting with value.
         */
        public function add_general_setting(string $key, $value): bool
        {
            return $this->update($key, $value);
        }

        /**
         * Remove general setting by key.
         */
        public function remove_general_setting(string $key): bool
        {
            $all = $this->get_all();
            if (array_key_exists($key, $all)) {
                unset($all[$key]);
                return (bool)update_option(self::OPTION_KEY, $all);
            }
            return true; // Already doesn't exist
        }

        /**
         * Sanitize notice data for safe storage.
         */
        public function sanitize_notice_data(array $notice_data): array
        {
            $sanitized = [];

            foreach ($notice_data as $key => $value) {
                switch ($key) {
                    case 'content':
                        $sanitized[$key] = sanitize_text_field($value);
                        break;

                    case 'excerpt':
                        $sanitized[$key] = sanitize_text_field($value);
                        break;

                    case 'source_plugin':
                        $sanitized[$key] = sanitize_text_field($value);
                        break;

                    case 'hidden_at':
                    case 'hidden_by_user_id':
                        $sanitized[$key] = absint($value);
                        break;

                    default:
                        if (is_string($value)) {
                            $sanitized[$key] = sanitize_text_field($value);
                        } else {
                            $sanitized[$key] = $value;
                        }
                        break;
                }
            }

            return $sanitized;
        }
    }
}