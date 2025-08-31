<?php
/**
 * Singleton Trait
 */

namespace DANI\Core;

if (!defined('ABSPATH')) {
    exit;
}

trait SingletonTrait
{
    /**
     * The single instance of the class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Constructor
     *
     * @return void
     */
    protected function __construct()
    {
    }

    /**
     * Get class instance.
     *
     * @return object Instance.
     */
    final public static function instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserializing.
     */
    final public function __wakeup()
    {
        if (function_exists('wc_doing_it_wrong')) {
            wc_doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', 'unnotifier'), '1.0');
        } else {
            wp_die(esc_html(__('Unserializing instances of this class is forbidden.', 'unnotifier')));
        }
    }
}