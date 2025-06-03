<?php
/**
 * Manage quizzes
 */

/**
 * Add Sync Quizzes button to the quiz list page
 */
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen->id === 'edit-quiz') {
        $url = add_query_arg('sync_quizzes', '1');
        echo '<div class="wrap">
            <a href="' . esc_url($url) . '" class="page-title-action page-title-action__sync-quizzes">Sync Quizzes</a>
        </div>';
    }
});

/**
 * Sync quizzes
 */
add_action('admin_init', function() {
    if (isset($_GET['sync_quizzes']) && current_user_can('manage_options')) {
        global $wpdb;
        $custom_quizzes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}enp_quiz");
        foreach ($custom_quizzes as $quiz) {
            // Check if post already exists
            $existing = get_posts([
                'post_type'   => 'quiz',
                'post_status' => 'any',
                'meta_query'  => [
                    [
                        'key'   => '_enp_quiz_id',
                        'value' => $quiz->quiz_id,
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);
            if (!empty($existing)) {
                $post_id = $existing[0];
            } else {
                $post_id = wp_insert_post([
                    'post_type' => 'quiz',
                    'post_title' => $quiz->quiz_title,
                    'post_status' => 'publish',
                ]);
                if ($post_id) {
                    update_post_meta($post_id, '_enp_quiz_id', $quiz->quiz_id);
                }
            }
            if ($post_id) {
                update_post_meta($post_id, 'quiz_status', $quiz->quiz_status);
                update_post_meta($post_id, 'quiz_owner', $quiz->quiz_owner);
                update_post_meta($post_id, 'quiz_created_at', $quiz->quiz_created_at);
                update_post_meta($post_id, 'quiz_views', $quiz->quiz_views);

                $sites = $wpdb->get_col($wpdb->prepare(
                    "SELECT eq.embed_quiz_url
                     FROM {$wpdb->prefix}enp_embed_quiz eq
                     WHERE eq.quiz_id = %d",
                    $quiz->quiz_id
                ));
                if (!empty($sites)) {
                    update_post_meta($post_id, 'embed_sites', implode(', ', $sites));
                } else {
                    update_post_meta($post_id, 'embed_sites', '');
                }
            }
        }
        // Optional: Add an admin notice
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Quizzes synced successfully!</p></div>';
        });
    }
});

/**
 * Delete quiz post and related data
 */
add_action('before_delete_post', function($post_id) {
    // Only target the 'quiz' post type
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'quiz') {
        return;
    }

    // Get the custom quiz_id from post meta
    $quiz_id = get_post_meta($post_id, '_enp_quiz_id', true);
    if (!$quiz_id) {
        return;
    }

    global $wpdb;

    // First delete from the child table (enp_embed_quiz)
    $wpdb->delete("{$wpdb->prefix}enp_embed_quiz", ['quiz_id' => $quiz_id]);

    // Then delete from the main custom quiz table
    $wpdb->delete("{$wpdb->prefix}enp_quiz", ['quiz_id' => $quiz_id]);

    // Clean up post meta
    delete_post_meta($post_id, '_enp_quiz_id');
    delete_post_meta($post_id, 'quiz_status');
    delete_post_meta($post_id, 'quiz_owner');
    delete_post_meta($post_id, 'quiz_created_at');
    delete_post_meta($post_id, 'quiz_views');
    delete_post_meta($post_id, 'embed_sites');
});

/**
 * Search quizzes
 */
add_filter('posts_search', function($search, $query) {
    global $wpdb;

    if (
        is_admin() &&
        $query->is_main_query() &&
        $query->get('post_type') === 'quiz' &&
        $query->is_search()
    ) {
        $s = $query->get('s');
        if (empty($s)) {
            return $search;
        }

        $like = '%' . $wpdb->esc_like($s) . '%';

        // Start search clause
        $search = $wpdb->prepare(" AND ( {$wpdb->posts}.post_title LIKE %s ", $like);

        // Search in post meta (quiz_status, quiz_created_at, quiz_views, embed_sites, _enp_quiz_id)
        $meta_fields = ['quiz_status', 'quiz_created_at', 'quiz_views', 'embed_sites', '_enp_quiz_id'];
        foreach ($meta_fields as $meta_key) {
            $search .= $wpdb->prepare(" OR EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = {$wpdb->posts}.ID
                AND pm.meta_key = %s
                AND pm.meta_value LIKE %s
            )", $meta_key, $like);
        }

        // Search by owner username in post meta
        $search .= $wpdb->prepare(" OR EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->users} u ON pm.meta_value = u.ID
            WHERE pm.post_id = {$wpdb->posts}.ID
            AND pm.meta_key = 'quiz_owner'
            AND u.user_login LIKE %s
        )", $like);

        // Search in custom enp_quiz table (quiz_id, quiz_title, quiz_status, quiz_created_at, quiz_views)
        $search .= $wpdb->prepare(" OR EXISTS (
            SELECT 1 FROM {$wpdb->prefix}enp_quiz eq
            WHERE eq.quiz_id = (
                SELECT pm2.meta_value FROM {$wpdb->postmeta} pm2
                WHERE pm2.post_id = {$wpdb->posts}.ID
                AND pm2.meta_key = '_enp_quiz_id'
                LIMIT 1
            )
            AND (
                eq.quiz_id LIKE %s
                OR eq.quiz_title LIKE %s
                OR eq.quiz_status LIKE %s
                OR eq.quiz_created_at LIKE %s
                OR eq.quiz_views LIKE %s
            )
        )", $like, $like, $like, $like, $like);

        // Search by owner username in custom enp_quiz table
        $search .= $wpdb->prepare(" OR EXISTS (
            SELECT 1 FROM {$wpdb->prefix}enp_quiz eq
            INNER JOIN {$wpdb->users} u ON eq.quiz_owner = u.ID
            WHERE eq.quiz_id = (
                SELECT pm2.meta_value FROM {$wpdb->postmeta} pm2
                WHERE pm2.post_id = {$wpdb->posts}.ID
                AND pm2.meta_key = '_enp_quiz_id'
                LIMIT 1
            )
            AND u.user_login LIKE %s
        )", $like);

        $search .= ')';
    }

    return $search;
}, 10, 2);