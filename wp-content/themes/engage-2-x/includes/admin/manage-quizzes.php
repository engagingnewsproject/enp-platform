<?php
/**
 * Manage quizzes
 */

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ENP_Quiz_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'Quiz',
            'plural'   => 'Quizzes',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'              => '',
            'quiz_id'         => 'ID',
            'quiz_title'      => 'Title',
            'quiz_status'     => 'Status',
            'quiz_owner'      => 'Owner',
            'quiz_created_at' => 'Created At',
            'quiz_views'      => 'Views',
        ];
    }

    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="quiz_id[]" value="%s" />', $item->quiz_id);
    }

    public function column_quiz_title($item) {
        $edit_link = admin_url('admin.php?page=manage_quizzes&action=edit&quiz_id=' . $item->quiz_id);
        return sprintf('<a href="%s">%s</a>', esc_url($edit_link), esc_html($item->quiz_title));
    }

    public function column_default($item, $column_name) {
        return isset($item->$column_name) ? esc_html($item->$column_name) : '';
    }

    public function get_bulk_actions() {
        return [
            'delete' => 'Delete'
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'enp_quiz';

        $per_page     = 20;
        $current_page = $this->get_pagenum();
        $search       = isset($_REQUEST['s']) ? wp_unslash($_REQUEST['s']) : '';

        // Bulk delete
        if ('delete' === $this->current_action() && !empty($_REQUEST['quiz_id'])) {
            $ids = array_map('intval', $_REQUEST['quiz_id']);
            $ids_sql = implode(',', $ids);
            $wpdb->query("DELETE FROM $table_name WHERE quiz_id IN ($ids_sql)");
        }

        // Search
        $where = '';
        if (!empty($search)) {
            $where = $wpdb->prepare("WHERE quiz_title LIKE %s", '%' . $wpdb->esc_like($search) . '%');
        }

        // Get total items
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");

        // Get items for current page
        $offset = ($current_page - 1) * $per_page;
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name $where ORDER BY quiz_id DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        if ($wpdb->last_error) {
            // error_log('SQL Error: ' . $wpdb->last_error);
        }

        // error_log(print_r($items, true));

        $this->items = array_map(function($item) { return (object) $item; }, $items);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    public function no_items() {
        _e('No quizzes found.');
    }

    public function single_row($item) {
        echo '<tr>';
        foreach ($this->get_columns() as $column_name => $column_display_name) {
            switch ($column_name) {
                case 'cb':
                    echo '<th scope="row" class="check-column">' . $this->column_cb($item) . '</th>';
                    break;
                case 'quiz_title':
                    echo '<td>' . $this->column_quiz_title($item) . '</td>';
                    break;
                default:
                    echo '<td>' . $this->column_default($item, $column_name) . '</td>';
            }
        }
        echo '</tr>';
    }

    public function display() {
        $this->display_tablenav('top');
        echo '<table class="wp-list-table ' . implode(' ', $this->get_table_classes()) . '">';
        echo '<thead>';
        parent::print_column_headers();
        echo '</thead>';
        echo '<tbody id="the-list">';
        $this->display_rows_or_placeholder();
        echo '</tbody>';
        echo '<tfoot>';
        parent::print_column_headers();
        echo '</tfoot>';
        echo '</table>';
        $this->display_tablenav('bottom');
    }

    public function display_rows_or_placeholder() {
        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $this->single_row($item);
            }
        } else {
            echo '<tr class="no-items"><td class="colspanchange" colspan="' . count($this->get_columns()) . '">';
            $this->no_items();
            echo '</td></tr>';
        }
    }
}

// Register the admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'Manage Quizzes',
        'Quizzes',
        'manage_options',
        'manage_quizzes',
        function() {
            echo '<div class="wrap"><h1>All Quizzes</h1>';
            $quizTable = new ENP_Quiz_List_Table();
            $quizTable->prepare_items();
            echo '<form method="post">';
            echo '<input type="hidden" name="page" value="manage_quizzes" />';
            $quizTable->search_box('Search Quizzes', 'quiz_search');
            $quizTable->display();
            echo '</form></div>';
        },
        'dashicons-welcome-learn-more',
        6
    );
});
