<?php

/**
 * Register Quiz post type and custom table integration.
 *
 * This class handles the registration of the Quiz custom post type
 * and its integration with custom database tables.
 *
 * @package Engage\Managers\Structures\PostTypes
 */

namespace Engage\Managers\Structures\PostTypes;

class Quiz {
    /**
     * Initialize the Quiz post type by registering necessary WordPress hooks.
     *
     * @return void
     */
    public function run() {
        add_action('init', [$this, 'register']);
        add_action('admin_menu', [$this, 'remove_add_new_button']);
        add_action('admin_head', [$this, 'hide_add_new_button']);
        
        // Add custom columns
        add_filter('manage_edit-quiz_columns', [$this, 'add_quiz_columns']);
        add_action('manage_quiz_posts_custom_column', [$this, 'populate_quiz_columns'], 10, 2);
    }

    /**
     * Register the Quiz custom post type.
     *
     * Sets up labels, capabilities, and configuration for the Quiz post type.
     * Configures admin UI and custom table integration.
     *
     * @return void
     */
    public function register() {
        $labels = array(
            'name' => _x('Quizzes', 'post type general name'),
            'singular_name' => _x('Quiz', 'post type singular name'),
            'add_new' => _x('Add New', 'quiz'),
            'add_new_item' => __('Add New Quiz'),
            'edit_item' => __('Edit Quiz'),
            'new_item' => __('New Quiz'),
            'all_items' => __('All Quizzes'),
            'view_item' => __('View Quiz'),
            'search_items' => __('Search Quizzes'),
            'not_found' => __('No quizzes found'),
            'not_found_in_trash' => __('No quizzes found in trash'),
            'parent_item_colon' => '',
            'menu_name' => 'Quizzes'
        );

        $args = array(
            'labels' => $labels,
            'description' => '',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => array('title'),
            'has_archive' => false,
            'exclude_from_search' => true,
            'show_in_rest' => false,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'can_export' => false,
            'delete_with_user' => false,
            'show_in_nav_menus' => false
        );

        register_post_type('quiz', $args);
    }

    /**
     * Remove "Add New" from submenu
     */
    public function remove_add_new_button() {
        global $submenu;
        if (isset($submenu['edit.php?post_type=quiz'])) {
            unset($submenu['edit.php?post_type=quiz'][10]);
        }
    }

    /**
     * Hide "Add New" button on the list screen
     */
    public function hide_add_new_button() {
        $screen = get_current_screen();
        if ($screen->post_type === 'quiz') {
            echo '<style>.page-title-action:not(.page-title-action__sync-quizzes) { display: none !important; }</style>';
        }
    }

    /**
     * Modify the WHERE clause for quiz queries
     */
    public function modify_quiz_query($where, $query) {
        global $wpdb;
        if (!$this->is_quiz_list_query($query)) {
            return $where;
        }

        $where .= " AND {$wpdb->posts}.ID IN (
            SELECT DISTINCT q.quiz_id 
            FROM {$wpdb->prefix}enp_quiz q
        )";
        
        return $where;
    }

    /**
     * Modify the JOIN clause for quiz queries
     */
    public function modify_quiz_join($join, $query) {
        global $wpdb;
        if (!$this->is_quiz_list_query($query)) {
            return $join;
        }

        $join .= " LEFT JOIN {$wpdb->prefix}enp_quiz q ON {$wpdb->posts}.ID = q.quiz_id";
        return $join;
    }

    /**
     * Modify the fields selected in quiz queries
     */
    public function modify_quiz_fields($fields, $query) {
        if (!$this->is_quiz_list_query($query)) {
            return $fields;
        }

        $fields .= ", q.quiz_status, q.quiz_owner, q.quiz_created_at, q.quiz_views";
        return $fields;
    }

    /**
     * Modify the GROUP BY clause for quiz queries
     */
    public function modify_quiz_groupby($groupby, $query) {
        global $wpdb;
        if (!$this->is_quiz_list_query($query)) {
            return $groupby;
        }

        return "{$wpdb->posts}.ID";
    }

    /**
     * Check if the current query is for the quiz list table
     */
    private function is_quiz_list_query($query) {
        return (
            is_admin() && 
            $query->is_main_query() && 
            isset($_GET['post_type']) && 
            $_GET['post_type'] === 'quiz'
        );
    }

    /**
     * Add custom columns to the quiz list table
     */
    public function add_quiz_columns($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['quiz_status'] = 'Status';
                $new_columns['quiz_owner'] = 'Owner';
                $new_columns['quiz_created_at'] = 'Created At';
                $new_columns['quiz_views'] = 'Views';
                $new_columns['embed_sites'] = 'Embed Sites';
            }
        }
        return $new_columns;
    }

    /**
     * Populate custom columns in the quiz list table
     */
    public function populate_quiz_columns($column, $post_id) {
        global $wpdb;
		$quiz_id = get_post_meta($post_id, '_enp_quiz_id', true);
		if (!$quiz_id) return;
		
        switch ($column) {
            case 'quiz_status':
                $status = $wpdb->get_var($wpdb->prepare(
                    "SELECT quiz_status FROM {$wpdb->prefix}enp_quiz WHERE quiz_id = %d",
                    $quiz_id
                ));
                echo esc_html($status);
                break;

            case 'quiz_owner':
                $owner_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT quiz_owner FROM {$wpdb->prefix}enp_quiz WHERE quiz_id = %d",
                    $quiz_id
                ));
                $user = get_user_by('id', $owner_id);
                if ($user) {
                    $edit_link = admin_url('user-edit.php?user_id=' . $owner_id);
                    printf(
                        '<a href="%s" target="_blank">%s</a>',
                        esc_url($edit_link),
                        esc_html($user->user_login)
                    );
                }
                break;

            case 'quiz_created_at':
                $created = $wpdb->get_var($wpdb->prepare(
                    "SELECT quiz_created_at FROM {$wpdb->prefix}enp_quiz WHERE quiz_id = %d",
                    $quiz_id
                ));
                echo esc_html($created);
                break;

            case 'quiz_views':
                $views = $wpdb->get_var($wpdb->prepare(
                    "SELECT quiz_views FROM {$wpdb->prefix}enp_quiz WHERE quiz_id = %d",
                    $quiz_id
                ));
                echo esc_html($views);
                break;

            case 'embed_sites':
                $sites = $wpdb->get_results($wpdb->prepare(
                    "SELECT s.embed_site_url
                     FROM {$wpdb->prefix}enp_embed_site s
                     INNER JOIN {$wpdb->prefix}enp_embed_quiz eq ON s.embed_site_id = eq.embed_site_id
                     WHERE eq.quiz_id = %d",
					$quiz_id
                ));

                if (empty($sites)) {
                    echo '—';
                } else {
                    $urls = array_map(function($site) {
                        return sprintf(
                            '<a href="%s" target="_blank">%s</a>',
                            esc_url($site->embed_site_url),
                            esc_html($site->embed_site_url)
                        );
                    }, $sites);
                    echo implode('<br>', $urls);
                }
                break;
        }
    }
}