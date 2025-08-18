<?php

namespace DANI\Core;

use DANI\Core\Contracts\NoticeHandlerInterface;
use DANI\Data\Options;
use DANI\Core\Logger;

/**
 * Handles notice capture and processing operations
 * 
 * @package DANI\Core
 * @since 1.0.0
 */
class NoticeHandler implements NoticeHandlerInterface
{
    /**
     * @var Options
     */
    private $options;

    /**
     * WordPress notice hooks
     * 
     * @var array
     */
    private $notice_hooks = ['admin_notices', 'network_admin_notices', 'all_admin_notices'];

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
     * Capture and process notices from WordPress hooks
     * 
     * @return void
     */
    public function capture_notices(): void
    {
        global $dani_captured_notices_by_hook;

        $hidden_global = $this->options->get(Options::GLOBAL_HIDDEN_NOTICES_KEY, []);
        $hidden_user = $this->options->get_user_hidden_notices();

        $this->log_capture_start($hidden_global, $hidden_user);

        $notices_by_hook = $this->collect_notices_by_hook($hidden_global, $hidden_user);
        
        $total_notices = array_sum(array_map('count', $notices_by_hook));
        Logger::log("=== CAPTURE NOTICES END === Total notices to display: $total_notices");

        $dani_captured_notices_by_hook = $notices_by_hook;

        $this->setup_notice_printing();
    }

    /**
     * Check if a notice should be hidden
     * 
     * @param string $notice_id The notice identifier
     * @param array $hidden_user User-specific hidden notices
     * @param array $hidden_global Globally hidden notices
     * @return bool True if notice should be hidden
     */
    public function is_notice_hidden(string $notice_id, array $hidden_user, array $hidden_global): bool
    {
        // Check if notice is hidden globally (stored as associative array with metadata)
        $is_global_hidden = isset($hidden_global[$notice_id]);
        
        // Check if notice is hidden for user
        // New format: associative array with metadata (key = notice_id)
        $is_user_hidden = isset($hidden_user[$notice_id]);
        
        // Backward compatibility: check old format (simple array with notice_ids as values)
        if (!$is_user_hidden) {
            $is_user_hidden = in_array($notice_id, $hidden_user, true);
        }
        
        return $is_user_hidden || $is_global_hidden;
    }

    /**
     * Generate a unique identifier for a notice
     * 
     * @param string $content Notice content
     * @param mixed $callback Notice callback function
     * @return array Array with two hash parts
     */
    public function generate_notice_pair(string $content, $callback): array
    {
        $callback_string = $this->get_callback_string($callback);
        
        // Normalize content to make ID more stable
        $normalized_content = $this->normalize_notice_content($content);
        
        // Get plugin source for additional stability
        $source_plugin = $this->get_plugin_name_from_callback($callback);
        
        // Create more stable hash using normalized content, callback and source plugin
        $content_hash = substr(md5($normalized_content . '|' . $source_plugin), 0, 8);
        $callback_hash = substr(md5($callback_string), 0, 8);
        
        return [$content_hash, $callback_hash];
    }

    /**
     * Normalize notice content to make ID generation more stable
     * 
     * @param string $content Raw notice content
     * @return string Normalized content
     */
    private function normalize_notice_content(string $content): string
    {
        // Remove HTML tags but preserve structure
        $text = wp_strip_all_tags($content);
        
        // Remove common variable elements that change frequently
        $patterns = [
            // Remove dates in various formats
            '/\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/',
            '/\d{4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,2}/',
            '/\w+\s+\d{1,2},?\s+\d{4}/',
            
            // Remove times
            '/\d{1,2}:\d{2}(:\d{2})?(\s*[AP]M)?/i',
            
            // Remove numbers that might be counters, IDs, or versions
            '/\b\d+\.\d+\.\d+\b/', // Version numbers like 1.2.3
            '/\b\d{3,}\b/',        // Large numbers (likely IDs or counters)
            
            // Remove URLs
            '/https?:\/\/[^\s<>"]+/i',
            
            // Remove file paths
            '/[a-zA-Z]:[\\\\\\/][^\s<>"]+/',
            '/\/[^\s<>"]+/',
            
            // Remove common variable phrases
            '/\b(updated|modified|created|published)\s+(on|at|:)\s*\S+/i',
            '/\b(last|next)\s+(update|check|sync|backup)\s*:?\s*\S+/i',
            '/\b\d+\s+(seconds?|minutes?|hours?|days?|weeks?|months?|years?)\s+ago/i',
            
            // Remove numbers in parentheses (often counts)
            '/\(\d+\)/',
            
            // Remove session IDs, tokens, nonces
            '/\b[a-f0-9]{8,}\b/i',
        ];
        
        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '[VARIABLE]', $text);
        }
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Extract first meaningful sentences (usually the core message)
        $sentences = preg_split('/[.!?]+/', $text);
        $core_message = '';
        $word_count = 0;
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;
            
            $words = explode(' ', $sentence);
            $word_count += count($words);
            $core_message .= $sentence . '. ';
            
            // Stop after ~50 words to focus on core message
            if ($word_count >= 50) break;
        }
        
        return trim($core_message) ?: $text;
    }

    /**
     * Get callbacks for a specific hook
     * 
     * @param string $hook_name Hook name
     * @return array Hook callbacks organized by priority
     */
    public function get_hook_callbacks(string $hook_name): array
    {
        global $wp_filter;
        
        if (!isset($wp_filter[$hook_name])) {
            return [];
        }

        return $wp_filter[$hook_name]->callbacks ?? [];
    }

    /**
     * Check if callback belongs to this plugin
     * 
     * @param mixed $callback Callback to check
     * @return bool True if it's own callback
     */
    public function is_own_callback($callback): bool
    {
        if (is_array($callback) && count($callback) >= 2) {
            $object = $callback[0];
            $method = $callback[1];
            
            if (is_object($object)) {
                $class_name = get_class($object);
                return strpos($class_name, 'DANI\\') === 0 ||
                       strpos($class_name, 'DANI_') === 0 ||
                       in_array($method, ['capture_notices', 'print_notices'], true);
            }
        }
        
        if (is_string($callback)) {
            return strpos($callback, 'dani_') === 0;
        }
        
        return false;
    }

    /**
     * Safely call a notice callback
     * 
     * @param array $callback_data Callback data from WordPress hook
     * @return string Output from callback or empty string
     */
    public function call_notice_callback_safely(array $callback_data): string
    {
        $callback = $callback_data['function'] ?? null;
        $accepted_args = $callback_data['accepted_args'] ?? 1;
        
        if (!is_callable($callback)) {
            return '';
        }

        try {
            ob_start();
            
            if ($accepted_args <= 0) {
                call_user_func($callback);
            } else {
                call_user_func($callback);
            }
            
            return ob_get_clean() ?: '';
        } catch (Exception $e) {
            ob_end_clean();
            Logger::log('Error calling notice callback', [
                'callback' => $this->get_callback_string($callback),
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Log the start of notice capture process
     * 
     * @param array $hidden_global Global hidden notices
     * @param array $hidden_user User hidden notices
     * @return void
     */
    private function log_capture_start(array $hidden_global, array $hidden_user): void
    {
        Logger::log('=== CAPTURE NOTICES START ===');
        Logger::log('Hidden global count: ' . count($hidden_global), $hidden_global);
        Logger::log('Hidden user count: ' . count($hidden_user), $hidden_user);
    }

    /**
     * Collect notices from all hooks
     * 
     * @param array $hidden_global Global hidden notices
     * @param array $hidden_user User hidden notices
     * @return array Notices organized by hook
     */
    private function collect_notices_by_hook(array $hidden_global, array $hidden_user): array
    {
        $notices_by_hook = array_fill_keys($this->notice_hooks, []);

        foreach ($this->notice_hooks as $hook_name) {
            $callbacks_map = $this->get_hook_callbacks($hook_name);
            if (empty($callbacks_map)) {
                continue;
            }

            Logger::log("Processing hook: $hook_name with " . count($callbacks_map) . " priority levels");

            $notices_by_hook[$hook_name] = $this->process_hook_callbacks(
                $hook_name, 
                $callbacks_map, 
                $hidden_global, 
                $hidden_user
            );
        }

        return $notices_by_hook;
    }

    /**
     * Process callbacks for a specific hook
     * 
     * @param string $hook_name Hook name
     * @param array $callbacks_map Callbacks organized by priority
     * @param array $hidden_global Global hidden notices
     * @param array $hidden_user User hidden notices
     * @return array Processed notices for this hook
     */
    private function process_hook_callbacks(string $hook_name, array $callbacks_map, array $hidden_global, array $hidden_user): array
    {
        $notices = [];
        ksort($callbacks_map);

        foreach ($callbacks_map as $priority => $callbacks) {
            foreach ((array)$callbacks as $callback_name => $callback_data) {
                if ($this->is_own_callback($callback_data['function'] ?? null)) {
                    continue;
                }

                $output = $this->call_notice_callback_safely($callback_data);
                if (empty($output) || !is_string($output)) {
                    continue;
                }

                $notice = $this->process_single_notice(
                    $hook_name,
                    $priority,
                    $callback_data,
                    $output,
                    $hidden_global,
                    $hidden_user
                );

                if ($notice) {
                    $notices[] = $notice;
                }
            }
        }

        return $notices;
    }

    /**
     * Process a single notice
     * 
     * @param string $hook_name Hook name
     * @param int $priority Callback priority
     * @param array $callback_data Callback data
     * @param string $output Notice output
     * @param array $hidden_global Global hidden notices
     * @param array $hidden_user User hidden notices
     * @return array|null Notice data or null if hidden
     */
    private function process_single_notice(string $hook_name, int $priority, array $callback_data, string $output, array $hidden_global, array $hidden_user): ?array
    {
        [$notice_id1, $notice_id2] = $this->generate_notice_pair($output, $callback_data['function'] ?? null);
        $notice_id = $notice_id1 . '_' . $notice_id2;

        // Determine source plugin name
        $source_plugin = $this->get_plugin_name_from_callback($callback_data['function'] ?? null);

        $this->log_notice_info($hook_name, $priority, $callback_data, $notice_id, $output);

        if ($this->is_notice_hidden($notice_id, $hidden_user, $hidden_global)) {
            Logger::log("Notice $notice_id is HIDDEN, skipping");
            return null;
        }

        Logger::log("Notice $notice_id will be DISPLAYED from plugin: $source_plugin");

        return [
            'id' => $notice_id,
            'content' => $this->prepare_notice_markup($output),
            'source_plugin' => $source_plugin,
        ];
    }

    /**
     * Log information about a notice
     * 
     * @param string $hook_name Hook name
     * @param int $priority Priority
     * @param array $callback_data Callback data
     * @param string $notice_id Notice ID
     * @param string $output Notice output
     * @return void
     */
    private function log_notice_info(string $hook_name, int $priority, array $callback_data, string $notice_id, string $output): void
    {
        $callback_info = $this->get_callback_string($callback_data['function'] ?? null);

        Logger::log("Notice generated", [
            'hook' => $hook_name,
            'priority' => $priority,
            'callback' => $callback_info,
            'notice_id' => $notice_id,
            'content_length' => strlen($output),
            'content_preview' => substr(wp_strip_all_tags($output), 0, 100)
        ]);
    }

    /**
     * Setup notice printing after capture
     * 
     * @return void
     */
    private function setup_notice_printing(): void
    {
        // Clear original hooks to prevent double output
        $this->clear_notices_hooks();

        // Add our print method to display notices
        foreach ($this->notice_hooks as $hook_name) {
            add_action($hook_name, function() use ($hook_name) {
                // This will be handled by NoticeRenderer, pass hook name
                do_action('dani_print_notices', $hook_name);
            }, 9999);
        }
    }

    /**
     * Clear all notice hooks from WordPress
     * 
     * @return void
     */
    private function clear_notices_hooks(): void
    {
        foreach ($this->notice_hooks as $hook_name) {
            remove_all_actions($hook_name);
        }
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
     * Convert callback to string representation
     * 
     * @param mixed $callback Callback to convert
     * @return string String representation
     */
    private function get_callback_string($callback): string
    {
        if (is_array($callback) && count($callback) >= 2) {
            $object = $callback[0];
            $method = $callback[1];
            
            if (is_object($object)) {
                return get_class($object) . '::' . $method;
            }
            
            return $object . '::' . $method;
        }
        
        return is_string($callback) ? $callback : 'unknown_callback';
    }

    /**
     * Get plugin name from callback function
     * 
     * @param mixed $callback Callback to analyze
     * @return string Plugin name or fallback
     */
    private function get_plugin_name_from_callback($callback): string
    {
        $plugin_name = 'Unknown Plugin';
        
        try {
            if (is_array($callback) && count($callback) >= 2) {
                $object = $callback[0];
                $method = $callback[1];
                
                if (is_object($object)) {
                    // Use reflection to get file path
                    $reflection = new \ReflectionClass($object);
                    $file_path = $reflection->getFileName();
                    
                    if ($file_path) {
                        $plugin_name = $this->extract_plugin_name_from_path($file_path);
                    }
                } elseif (is_string($object)) {
                    // Static method call
                    if (class_exists($object)) {
                        $reflection = new \ReflectionClass($object);
                        $file_path = $reflection->getFileName();
                        
                        if ($file_path) {
                            $plugin_name = $this->extract_plugin_name_from_path($file_path);
                        }
                    }
                }
            } elseif (is_string($callback)) {
                // Function name
                if (function_exists($callback)) {
                    $reflection = new \ReflectionFunction($callback);
                    $file_path = $reflection->getFileName();
                    
                    if ($file_path) {
                        $plugin_name = $this->extract_plugin_name_from_path($file_path);
                    }
                }
            } elseif (is_object($callback) && method_exists($callback, '__invoke')) {
                // Callable object
                $reflection = new \ReflectionClass($callback);
                $file_path = $reflection->getFileName();
                
                if ($file_path) {
                    $plugin_name = $this->extract_plugin_name_from_path($file_path);
                }
            }
        } catch (\Exception $e) {
            Logger::log('Error determining plugin name from callback', [
                'callback' => $this->get_callback_string($callback),
                'error' => $e->getMessage()
            ]);
        }
        
        return $plugin_name;
    }

    /**
     * Extract plugin name from file path
     * 
     * @param string $file_path Path to the file
     * @return string Plugin name
     */
    private function extract_plugin_name_from_path(string $file_path): string
    {
        // Normalize path separators
        $file_path = str_replace('\\', '/', $file_path);
        
        // Check if it's in wp-content/plugins/
        if (strpos($file_path, '/wp-content/plugins/') !== false) {
            $parts = explode('/wp-content/plugins/', $file_path);
            if (count($parts) > 1) {
                $plugin_parts = explode('/', $parts[1]);
                $plugin_folder = $plugin_parts[0];
                
                // Try to get plugin name from plugin header
                $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_folder . '/' . $plugin_folder . '.php';
                if (file_exists($plugin_file)) {
                    $plugin_name = $this->get_plugin_name_from_file($plugin_file);
                    if ($plugin_name) {
                        return $plugin_name;
                    }
                }
                
                // Try main plugin file
                $main_files = glob(WP_PLUGIN_DIR . '/' . $plugin_folder . '/*.php');
                foreach ($main_files as $main_file) {
                    $plugin_name = $this->get_plugin_name_from_file($main_file);
                    if ($plugin_name) {
                        return $plugin_name;
                    }
                }
                
                // Fallback to folder name
                return $this->humanize_plugin_folder_name($plugin_folder);
            }
        }
        
        // Check if it's WordPress core
        if (strpos($file_path, '/wp-admin/') !== false || strpos($file_path, '/wp-includes/') !== false) {
            return 'WordPress Core';
        }
        
        // Check if it's a theme
        if (strpos($file_path, '/wp-content/themes/') !== false) {
            $parts = explode('/wp-content/themes/', $file_path);
            if (count($parts) > 1) {
                $theme_parts = explode('/', $parts[1]);
                $theme_folder = $theme_parts[0];
                return 'Theme: ' . $this->humanize_plugin_folder_name($theme_folder);
            }
        }
        
        return 'Unknown Plugin';
    }

    /**
     * Get plugin name from plugin file header
     * 
     * @param string $plugin_file Path to plugin file
     * @return string|null Plugin name or null
     */
    private function get_plugin_name_from_file(string $plugin_file): ?string
    {
        if (!file_exists($plugin_file)) {
            return null;
        }
        
        $file_content = file_get_contents($plugin_file, false, null, 0, 8192);
        if ($file_content === false) {
            return null;
        }
        
        // Look for Plugin Name header
        if (preg_match('/Plugin Name:\s*(.+)/i', $file_content, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Convert plugin folder name to human readable format
     * 
     * @param string $folder_name Plugin folder name
     * @return string Humanized name
     */
    private function humanize_plugin_folder_name(string $folder_name): string
    {
        // Replace common separators with spaces
        $name = str_replace(['-', '_'], ' ', $folder_name);
        
        // Capitalize each word
        $name = ucwords($name);
        
        return $name;
    }
}