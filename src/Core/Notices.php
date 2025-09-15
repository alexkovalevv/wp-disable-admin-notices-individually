<?php

namespace UNNO\Core;

/**
 * Main coordinator class for Disable Admin Notices Individually
 * 
 * This class acts as a facade coordinating between specialized components:
 * - NoticeHandler: Captures and processes notices
 * - NoticeRenderer: Renders notices with buttons
 * - AjaxHandler: Handles AJAX requests
 * - ModeManager: Manages display modes
 * 
 * @package UNNO\Core
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

use UNNO\Data\Options;

if (!class_exists('UNNO\Core\Notices')) {
    class Notices
    {
        use SingletonTrait;
        
        /**
         * @var Options
         */
        private $options;

        /**
         * @var NoticeHandler
         */
        private $notice_handler;

        /**
         * @var NoticeRenderer
         */
        private $notice_renderer;

        /**
         * @var AjaxHandler
         */
        private $ajax_handler;

        /**
         * @var ModeManager
         */
        private $mode_manager;


        /**
         * Constructor - Initialize dependencies and hooks
         */
        protected function __construct()
        {
            $this->init_dependencies();
            $this->init_hooks();
        }

        /**
         * Initialize plugin dependencies
         * 
         * @return void
         */
        private function init_dependencies(): void
        {
            $this->options = Options::instance();
            $this->notice_handler = new NoticeHandler($this->options);
            $this->notice_renderer = new NoticeRenderer();
            $this->ajax_handler = new AjaxHandler($this->options);
            $this->mode_manager = new ModeManager($this->options, $this->notice_handler);
        }

        /**
         * Initialize WordPress hooks
         * 
         * @return void
         */
        private function init_hooks(): void
        {
            add_action('init', [$this, 'plugin_init']);

            if (is_admin()) {
                add_action('wp_ajax_unno_hide_notice', [$this->ajax_handler, 'ajax_hide_notice']);
                add_action('wp_ajax_unno_reset_notices', [$this->ajax_handler, 'ajax_reset_notices']);
                add_action('wp_ajax_unno_restore_single_notice', [$this->ajax_handler, 'ajax_restore_single_notice']);
                add_action('unno_print_notices', [$this->notice_renderer, 'print_notices']);
            }
        }

        /**
         * Initialize plugin functionality
         * 
         * @return void
         */
        public function plugin_init(): void
        {
            if (is_admin()) {
                $this->mode_manager->init_notice_handling();
            }
        }

        /**
         * Get the options instance
         * 
         * @return Options
         */
        public function get_options(): Options
        {
            return $this->options;
        }

        /**
         * Get the notice handler instance
         * 
         * @return NoticeHandler
         */
        public function get_notice_handler(): NoticeHandler
        {
            return $this->notice_handler;
        }

        /**
         * Get the notice renderer instance
         * 
         * @return NoticeRenderer
         */
        public function get_notice_renderer(): NoticeRenderer
        {
            return $this->notice_renderer;
        }

        /**
         * Get the ajax handler instance
         * 
         * @return AjaxHandler
         */
        public function get_ajax_handler(): AjaxHandler
        {
            return $this->ajax_handler;
        }

        /**
         * Get the mode manager instance
         * 
         * @return ModeManager
         */
        public function get_mode_manager(): ModeManager
        {
            return $this->mode_manager;
        }


        /**
         * Get current plugin version
         * 
         * @return string
         */
        public function get_version(): string
        {
            return defined('UNNO_VERSION') ? UNNO_VERSION : '1.0.0';
        }

        /**
         * Check if plugin is in debug mode
         * 
         * @return bool
         */
        public function is_debug_enabled(): bool
        {
            return $this->options->get('debug', false);
        }

        /**
         * Log a debug message if debug is enabled
         * 
         * @param string $message Log message
         * @param array $context Additional context
         * @return void
         */
        public function debug_log(string $message, array $context = []): void
        {
        }
    }
}