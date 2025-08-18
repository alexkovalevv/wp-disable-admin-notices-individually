<?php

namespace DANI\Core;

/**
 * Centralized logging for Disable Admin Notices Individually
 */
if (!defined('ABSPATH')) {
    exit;
}

use DANI\Data\Options;

if (!class_exists('DANI\Core\Logger')) {
    class Logger
    {
        /**
         * Write a log message if debug is enabled in options.
         */
        public static function log($message, $context = [])
        {
            // Проверяем включена ли отладка
            if (!Options::instance()->get('debug', false)) {
                return;
            }

            self::write_log_entry($message, $context);
        }

        /**
         * Force write a log message regardless of debug settings
         */
        public static function log_force($message, $context = [])
        {
            self::write_log_entry($message, $context);
        }

        /**
         * Internal method to write log entry
         */
        private static function write_log_entry($message, $context = [])
        {
            $upload_dir = wp_upload_dir();
            $log_dir = trailingslashit($upload_dir['basedir']) . 'dani-logs/';

            // Create directory if it doesn't exist
            if (!file_exists($log_dir)) {
                wp_mkdir_p($log_dir);

                // Protect directory from direct access
                global $wp_filesystem;
                if (empty($wp_filesystem)) {
                    require_once ABSPATH . '/wp-admin/includes/file.php';
                    WP_Filesystem();
                }
                if ($wp_filesystem) {
                    $wp_filesystem->put_contents($log_dir . '.htaccess', "Order deny,allow\nDeny from all", FS_CHMOD_FILE);
                    $wp_filesystem->put_contents($log_dir . 'index.php', "<?php\n// Silence is golden\n", FS_CHMOD_FILE);
                }
            }

            $log_file = $log_dir . 'dani-debug-' . current_time('Y-m-d') . '.log';
            $timestamp = current_time('Y-m-d H:i:s');
            $line = sprintf('[%s] %s', $timestamp, (string)$message);

            if (!empty($context)) {
                $line .= ' | Context: ' . wp_json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            // Use WP Filesystem API for writing logs
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }
            
            $result = false;
            if ($wp_filesystem) {
                $existing_content = $wp_filesystem->exists($log_file) ? $wp_filesystem->get_contents($log_file) : '';
                $result = $wp_filesystem->put_contents($log_file, $existing_content . $line . PHP_EOL, FS_CHMOD_FILE);
            }

            if ($result === false) {
                // Log write failure - silently fail to comply with WordPress Plugin Check requirements
                // The plugin will continue to function even if logging fails
            }

            // Clean up old logs periodically
            if (wp_rand(1, 100) === 1) {
                self::cleanup_old_logs($log_dir);
            }
        }

        /**
         * Remove log files older than 7 days to save space.
         */
        private static function cleanup_old_logs($log_dir)
        {
            if (!is_dir($log_dir)) {
                return;
            }

            $files = glob(trailingslashit($log_dir) . 'dani-debug-*.log');
            if (!$files) {
                return;
            }

            $expire_time = time() - (7 * 24 * 60 * 60);

            foreach ($files as $file) {
                if (is_file($file) && @filemtime($file) < $expire_time) {
                    wp_delete_file($file);
                }
            }
        }

        /**
         * Helper to get the latest log file info.
         */
        public static function get_latest_log_info()
        {
            $upload_dir = wp_upload_dir();
            $dir = trailingslashit($upload_dir['basedir']) . 'dani-logs/';

            if (!is_dir($dir)) {
                return null;
            }

            $files = glob($dir . 'dani-debug-*.log');
            if (!$files) {
                return null;
            }

            usort($files, function ($a, $b) {
                return filemtime($b) <=> filemtime($a);
            });

            $file = $files[0];
            $size = @filesize($file);

            return [
                'path' => $file,
                'name' => basename($file),
                'size' => $size,
                'size_formatted' => $size ? size_format($size) : 'Unknown',
                'modified' => @filemtime($file),
                'modified_formatted' => wp_date('Y-m-d H:i:s', @filemtime($file))
            ];
        }

        /**
         * Clear all log files
         */
        public static function clear_all_logs()
        {
            $upload_dir = wp_upload_dir();
            $dir = trailingslashit($upload_dir['basedir']) . 'dani-logs/';

            if (!is_dir($dir)) {
                return true;
            }

            $files = glob($dir . 'dani-debug-*.log');
            if (!$files) {
                return true;
            }

            $cleared_count = 0;
            foreach ($files as $file) {
                if (wp_delete_file($file)) {
                    $cleared_count++;
                }
            }

            return $cleared_count > 0;
        }

        /**
         * Check if debug logging is enabled
         */
        public static function is_debug_enabled()
        {
            return (bool)Options::instance()->get('debug', false);
        }
    }
}