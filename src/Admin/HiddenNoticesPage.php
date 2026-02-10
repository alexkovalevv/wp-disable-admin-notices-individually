<?php

declare(strict_types=1);

namespace UNNO\Admin;

/**
 * Hidden Notices Page for Unnotifier plugin.
 * 
 * Displays a list table of all hidden notices (user and global) with pagination.
 * 
 * @package UNNO\Admin
 * @since 1.2.5
 */
if (!defined('ABSPATH')) {
    exit;
}

use UNNO\Data\Options;

// Include WP_List_Table if not already loaded
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class HiddenNoticesPage {
    
    /**
     * Options instance.
     * 
     * @var Options
     */
    private $options;
    
    /**
     * Constructor.
     * 
     * @since 1.2.5
     */
    public function __construct() {
        $this->options = Options::instance();
    }
    
    /**
     * Render the hidden notices page.
     * 
     * @since 1.2.5
     * @return void
     */
    public function render(): void {
        // Get counts
        $hidden_user = $this->options->get_user_hidden_notices();
        $hidden_user_count = count($hidden_user);
        $hidden_global = $this->options->get_global_hidden_notices();
        $hidden_global_count = count($hidden_global);
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Hidden Notices', 'unnotifier'); ?></h1>
            
            <div class="unno-reset-buttons" style="margin: 20px 0;">
                <button type="button" id="unno-reset-notices-user" class="button button-secondary">
                    <?php esc_html_e('Reset My Hidden Notices', 'unnotifier'); ?>
                </button>
                <?php if (current_user_can('manage_options')): ?>
                    <button type="button" id="unno-reset-notices-all" class="button button-secondary">
                        <?php esc_html_e('Reset Hidden Notices For All', 'unnotifier'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div id="unno-reset-message" style="display:none;"></div>
            
            <?php
            $list_table = new HiddenNoticesListTable();
            $list_table->prepare_items();
            $list_table->display();
            ?>
        </div>
        <?php
    }
}

/**
 * List table for hidden notices.
 * 
 * @package UNNO\Admin
 * @since 1.2.5
 */
class HiddenNoticesListTable extends \WP_List_Table {
    
    /**
     * Options instance.
     * 
     * @var Options
     */
    private $options;
    
    /**
     * Constructor.
     * 
     * @since 1.2.5
     */
    public function __construct() {
        parent::__construct([
            'singular' => __('Hidden Notice', 'unnotifier'),
            'plural' => __('Hidden Notices', 'unnotifier'),
            'ajax' => false,
        ]);
        
        $this->options = Options::instance();
    }
    
    /**
     * Get table columns.
     * 
     * @since 1.2.5
     * @return array Column definitions
     */
    public function get_columns(): array {
        return [
            'plugin' => __('Plugin/Theme', 'unnotifier'),
            'content' => __('Notice Content', 'unnotifier'),
            'hidden_by' => __('Hidden By', 'unnotifier'),
            'actions' => __('Actions', 'unnotifier'),
        ];
    }
    
    /**
     * Get sortable columns.
     * 
     * @since 1.2.5
     * @return array Sortable columns
     */
    protected function get_sortable_columns(): array {
        return [
            'plugin' => ['plugin', false],
            'hidden_by' => ['hidden_by', false],
        ];
    }
    
    /**
     * Prepare items for display.
     * 
     * @since 1.2.5
     * @return void
     */
    public function prepare_items(): void {
        // Get all notices (user + global)
        $all_notices = $this->get_all_notices();
        
        // Pagination
        $per_page = 30;
        $current_page = $this->get_pagenum();
        $total_items = count($all_notices);
        
        // Sort notices
        $orderby = sanitize_text_field($_GET['orderby'] ?? 'plugin');
        $order = sanitize_text_field($_GET['order'] ?? 'asc');
        
        if ($orderby && in_array($orderby, ['plugin', 'hidden_by'], true)) {
            usort($all_notices, function ($a, $b) use ($orderby, $order) {
                $a_val = $a[$orderby] ?? '';
                $b_val = $b[$orderby] ?? '';
                
                $result = strcasecmp($a_val, $b_val);
                
                return $order === 'asc' ? $result : -$result;
            });
        }

        // Column headers
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
        
        // Paginate
        $offset = ($current_page - 1) * $per_page;
        $this->items = array_slice($all_notices, $offset, $per_page);
        
        // Set pagination args
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }
    
    /**
     * Get all notices (user + global) as a unified list.
     * 
     * @since 1.2.5
     * @return array Unified list of notices
     */
    private function get_all_notices(): array {
        $current_user_id = get_current_user_id();
        $all_notices = [];

        $combined = $this->options->get_combined_hidden_notices();

        foreach ($combined['user'] as $notice_id => $notice_data) {
            $notice = $this->prepare_notice_data($notice_id, $notice_data, 'user', $current_user_id);
            if ($notice) {
                $all_notices[] = $notice;
            }
        }
        
        // Get global hidden notices (only if user can manage options)
        if (current_user_can('manage_options')) {
            foreach ($combined['global'] as $notice_id => $notice_data) {
                $notice = $this->prepare_notice_data($notice_id, $notice_data, 'global', null);
                if ($notice) {
                    $all_notices[] = $notice;
                }
            }
        }
        
        return $all_notices;
    }
    
    /**
     * Prepare notice data for display.
     * 
     * @since 1.2.5
     * @param string $notice_id Notice ID
     * @param mixed $notice_data Notice data (array or legacy format)
     * @param string $type Notice type ('user' or 'global')
     * @param int|null $user_id User ID for user notices
     * @return array|null Prepared notice data or null
     */
    private function prepare_notice_data(string $notice_id, $notice_data, string $type, ?int $user_id): ?array {
        // Handle both old format and new format
        if (is_array($notice_data)) {
            $source_plugin = $notice_data['source_plugin'] ?? 'Unknown Plugin';
            $excerpt = $notice_data['notice_excerpt'] ?? $notice_data['excerpt'] ?? '';
            $content = $notice_data['notice_content'] ?? $notice_data['content'] ?? $excerpt;
            $hidden_by_user_id = $notice_data['hidden_by_user_id'] ?? $user_id;
        } else {
            // Old format
            $source_plugin = 'Unknown Plugin';
            $excerpt = 'Legacy notice - ' . substr($notice_id, 0, 20);
            $content = $excerpt;
            $hidden_by_user_id = $user_id;
        }
        
        // Strip HTML and JS from content
        $content = wp_strip_all_tags($content);
        if (empty($content)) {
            $content = __('Administrative notice', 'unnotifier');
        }
        
        // Get user display name
        $hidden_by_name = __('Unknown', 'unnotifier');
        if ($hidden_by_user_id) {
            $user = get_userdata($hidden_by_user_id);
            if ($user) {
                $hidden_by_name = $user->display_name ?: $user->user_login;
            }
        }
        
        return [
            'notice_id' => $notice_id,
            'plugin' => $source_plugin,
            'content' => $content,
            'hidden_by' => $hidden_by_name,
            'hidden_by_user_id' => $hidden_by_user_id,
            'type' => $type,
        ];
    }
    
    /**
     * Column: Plugin/Theme.
     * 
     * @since 1.2.5
     * @param array $item Notice item
     * @return string Column content
     */
    protected function column_plugin(array $item): string {
        return '<strong>' . esc_html($item['plugin']) . '</strong>';
    }
    
    /**
     * Column: Notice Content.
     * 
     * @since 1.2.5
     * @param array $item Notice item
     * @return string Column content
     */
    protected function column_content(array $item): string {
        $content = esc_html($item['content']);
        // Truncate if too long
        if (mb_strlen($content) > 150) {
            $content = mb_substr($content, 0, 147) . '...';
        }
        return '<span title="' . esc_attr($item['content']) . '">' . $content . '</span>';
    }
    
    /**
     * Column: Hidden By.
     * 
     * @since 1.2.5
     * @param array $item Notice item
     * @return string Column content
     */
    protected function column_hidden_by(array $item): string {
        $output = esc_html($item['hidden_by']);
        if ($item['type'] === 'global') {
            $output .= ' <span class="description">(' . esc_html__('Global', 'unnotifier') . ')</span>';
        } else {
            $output .= ' <span class="description">(' . esc_html__('User', 'unnotifier') . ')</span>';
        }
        return $output;
    }
    
    /**
     * Column: Actions.
     * 
     * @since 1.2.5
     * @param array $item Notice item
     * @return string Column content
     */
    protected function column_actions(array $item): string {
        $target = $item['type'] === 'global' ? 'global' : 'user';
        return sprintf(
            '<button type="button" class="button button-small unno-restore-single-notice" data-target="%s" data-notice-id="%s">%s %s</button>',
            esc_attr($target),
            esc_attr($item['notice_id']),
            '<span class="dashicons dashicons-undo"></span>',
            esc_html__('Restore', 'unnotifier')
        );
    }
    
    /**
     * Default column output.
     * 
     * @since 1.2.5
     * @param array $item Notice item
     * @param string $column_name Column name
     * @return string Column content
     */
    protected function column_default($item, $column_name): string {
        return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
    }
    
    /**
     * Message when no items found.
     * 
     * @since 1.2.5
     * @return void
     */
    public function no_items(): void {
        esc_html_e('No hidden notices found.', 'unnotifier');
    }
}

