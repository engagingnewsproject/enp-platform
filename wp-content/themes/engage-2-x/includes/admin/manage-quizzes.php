<?php
/**
 * Manage quizzes
 */

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if (is_admin()) {
    /**
     * Custom list table for managing quizzes in WordPress admin
     */
    class ENP_Quiz_List_Table extends WP_List_Table {
        /**
         * Initialize the list table with quiz-specific settings
         */
        public function __construct() {
            parent::__construct([
                'singular' => 'Quiz',
                'plural'   => 'Quizzes',
                'ajax'     => false
            ]);
        }

        /**
         * Define the columns to be displayed in the quiz list table
         * @return array Column definitions
         */
        public function get_columns() {
            return [
                'cb'              => '', // leave blank, WP will render the checkbox
                'quiz_id'         => 'ID',
                'quiz_title'      => 'Title',
                'quiz_status'     => 'Status',
                'quiz_owner'      => 'Owner',
                'quiz_created_at' => 'Created At',
                'quiz_views'      => 'Views',
            ];
        }

        /**
         * Render the checkbox column for bulk actions
         * @param object $item The current quiz item
         * @return string HTML for the checkbox
         */
		public function column_cb($item) {
			return sprintf('<input type="checkbox" name="quiz_id[]" value="%s" />', $item->quiz_id);
		}

        /**
         * Render the quiz title column with edit link
         * @param object $item The current quiz item
         * @return string HTML for the title column
         */
        public function column_quiz_title($item) {
            $edit_link = admin_url('admin.php?page=manage_quizzes&action=edit&quiz_id=' . $item->quiz_id);
            return sprintf('<a href="%s">%s</a>', esc_url($edit_link), esc_html($item->quiz_title));
        }

        /**
         * Render the owner column with link to user profile
         * @param object $item The current quiz item
         * @return string HTML for the owner column
         */
        public function column_quiz_owner($item) {
            $user_id = intval($item->quiz_owner);
            if ($user_id) {
                $user = get_user_by('id', $user_id);
                if ($user) {
                    $edit_link = admin_url('user-edit.php?user_id=' . $user_id);
                    return sprintf('<a href="%s" target="_blank">%s</a>', esc_url($edit_link), esc_html($user->display_name));
                }
            }
            return esc_html($item->quiz_owner);
        }

        /**
         * Default column renderer
         * @param object $item The current quiz item
         * @param string $column_name The column name
         * @return string HTML for the column
         */
        public function column_default($item, $column_name) {
            return isset($item->$column_name) ? esc_html($item->$column_name) : '';
        }

        /**
         * Define bulk actions available for quizzes
         * @return array Bulk action definitions
         */
        public function get_bulk_actions() {
            return [
                'delete' => 'Delete'
            ];
        }

        /**
         * Define which columns are sortable
         * @return array Sortable column definitions
         */
        public function get_sortable_columns() {
            return [
                'quiz_id'         => ['quiz_id', false],
                'quiz_title'      => ['quiz_title', false],
                'quiz_status'     => ['quiz_status', false],
                'quiz_owner'      => ['quiz_owner', false],
                'quiz_created_at' => ['quiz_created_at', false],
                'quiz_views'      => ['quiz_views', false],
            ];
        }

        /**
         * Prepare the quiz items for display
         * Handles pagination, sorting, searching, and bulk actions
         */
        public function prepare_items() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'enp_quiz';

            $per_page     = 20;
            $current_page = $this->get_pagenum();
            $search       = isset($_REQUEST['s']) ? wp_unslash($_REQUEST['s']) : '';

            // Sorting
            $orderby = isset($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'quiz_id';
            $order   = (isset($_REQUEST['order']) && strtolower($_REQUEST['order']) === 'asc') ? 'ASC' : 'DESC';

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
                    "SELECT * FROM $table_name $where ORDER BY $orderby $order LIMIT %d OFFSET %d",
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

        /**
         * Display message when no quizzes are found
         */
        public function no_items() {
            _e('No quizzes found.');
        }

        /**
         * Render a single row in the table
         * @param object $item The current quiz item
         */
        public function single_row($item) {
            echo '<tr>';
            $this->single_row_columns($item);
            echo '</tr>';
        }

        /**
         * Display the table rows or a placeholder if no items exist
         */
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
}
