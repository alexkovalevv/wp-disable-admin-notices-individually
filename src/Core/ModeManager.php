<?php

namespace DANI\Core;

use DANI\Core\Contracts\ModeManagerInterface;
use DANI\Data\Options;

/**
 * Manages notice display modes (show_all, individual, hide_all)
 * 
 * @package DANI\Core
 * @since 1.0.0
 */
class ModeManager implements ModeManagerInterface
{
    /**
     * @var Options
     */
    private $options;

    /**
     * @var NoticeHandler
     */
    private $notice_handler;

    /**
     * Available modes
     * 
     * @var array
     */
    private $available_modes = ['show_all', 'individual', 'hide_all'];

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
     * @param NoticeHandler $notice_handler Notice handler instance
     */
    public function __construct(Options $options, NoticeHandler $notice_handler)
    {
        $this->options = $options;
        $this->notice_handler = $notice_handler;
    }

    /**
     * Initialize notice handling based on current mode
     * 
     * @return void
     */
    public function init_notice_handling(): void
    {
        $mode = $this->get_current_mode();

        switch ($mode) {
            case 'hide_all':
                $this->setup_hide_all_mode();
                break;

            case 'individual':
                $this->setup_individual_mode();
                break;

            case 'show_all':
            default:
                $this->setup_show_all_mode();
                break;
        }
    }

    /**
     * Hide all admin notices
     * 
     * @return void
     */
    public function hide_all_notices(): void
    {
        $this->clear_notices_hooks();
    }

    /**
     * Clear all notice hooks from WordPress
     * 
     * @return void
     */
    public function clear_notices_hooks(): void
    {
        foreach ($this->notice_hooks as $hook_name) {
            remove_all_actions($hook_name);
        }
    }

    /**
     * Get current plugin mode
     * 
     * @return string Current mode (show_all, individual, hide_all)
     */
    public function get_current_mode(): string
    {
        $mode = $this->options->get('mode', 'individual');
        
        // Validate mode
        if (!in_array($mode, $this->available_modes, true)) {
            $mode = 'individual';
        }
        
        return $mode;
    }

    /**
     * Set plugin mode
     * 
     * @param string $mode New mode to set
     * @return bool True on success, false on failure
     */
    public function set_mode(string $mode): bool
    {
        if (!in_array($mode, $this->available_modes, true)) {
            return false;
        }

        return $this->options->update('mode', $mode);
    }

    /**
     * Get list of available modes
     * 
     * @return array Available modes with descriptions
     */
    public function get_available_modes(): array
    {
        return [
            'show_all' => [
                'label' => __('Show All Notices', 'disable-admin-notices-individually'),
                'description' => __('Display all admin notices without modification.', 'disable-admin-notices-individually')
            ],
            'individual' => [
                'label' => __('Individual Control', 'disable-admin-notices-individually'),
                'description' => __('Show notices with hide buttons for individual control.', 'disable-admin-notices-individually')
            ],
            'hide_all' => [
                'label' => __('Hide All Notices', 'disable-admin-notices-individually'),
                'description' => __('Hide all admin notices completely.', 'disable-admin-notices-individually')
            ]
        ];
    }

    /**
     * Check if mode is valid
     * 
     * @param string $mode Mode to validate
     * @return bool True if valid
     */
    public function is_valid_mode(string $mode): bool
    {
        return in_array($mode, $this->available_modes, true);
    }

    /**
     * Setup hide all mode
     * 
     * @return void
     */
    private function setup_hide_all_mode(): void
    {
        // Remove all notice hooks early
        add_action('admin_print_scripts', [$this, 'hide_all_notices'], 1);
    }

    /**
     * Setup individual mode with notice capture and buttons
     * 
     * @return void
     */
    private function setup_individual_mode(): void
    {
        // Capture notices late to get all of them
        add_action('admin_print_scripts', [$this->notice_handler, 'capture_notices'], 999);
    }

    /**
     * Setup show all mode (default WordPress behavior)
     * 
     * @return void
     */
    private function setup_show_all_mode(): void
    {
        // Do nothing - let WordPress handle notices normally
    }

    /**
     * Handle mode change
     * 
     * @param string $new_mode New mode to switch to
     * @return array Result with success status and message
     */
    public function handle_mode_change(string $new_mode): array
    {
        if (!$this->is_valid_mode($new_mode)) {
            return [
                'success' => false,
                'message' => __('Invalid mode specified.', 'disable-admin-notices-individually')
            ];
        }

        $old_mode = $this->get_current_mode();
        
        if ($old_mode === $new_mode) {
            return [
                'success' => true,
                'message' => __('Mode is already set to the selected value.', 'disable-admin-notices-individually')
            ];
        }

        if ($this->set_mode($new_mode)) {
            return [
                'success' => true,
                'message' => sprintf(
                    // translators: %1$s is the old mode name, %2$s is the new mode name
                    __('Mode changed from %1$s to %2$s.', 'disable-admin-notices-individually'),
                    $this->get_mode_label($old_mode),
                    $this->get_mode_label($new_mode)
                )
            ];
        }

        return [
            'success' => false,
            'message' => __('Failed to update mode.', 'disable-admin-notices-individually')
        ];
    }

    /**
     * Get mode label
     * 
     * @param string $mode Mode identifier
     * @return string Human readable label
     */
    public function get_mode_label(string $mode): string
    {
        $modes = $this->get_available_modes();
        return $modes[$mode]['label'] ?? $mode;
    }

    /**
     * Get mode description
     * 
     * @param string $mode Mode identifier
     * @return string Mode description
     */
    public function get_mode_description(string $mode): string
    {
        $modes = $this->get_available_modes();
        return $modes[$mode]['description'] ?? '';
    }

    /**
     * Reset all mode-related settings
     * 
     * @return bool True on success
     */
    public function reset_mode_settings(): bool
    {
        return $this->set_mode('individual');
    }
}