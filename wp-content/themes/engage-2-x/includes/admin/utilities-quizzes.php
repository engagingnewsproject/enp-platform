<?php
/**
 * Manage quizzes
 */

/**
 * Sync quizzes
 */
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $custom_quizzes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}enp_quiz");
    foreach ($custom_quizzes as $quiz) {
        // Check if post already exists
        $existing = get_posts([
            'post_type' => 'quiz',
            'meta_key' => '_enp_quiz_id',
            'meta_value' => $quiz->quiz_id,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        if ($existing) continue;

        $post_id = wp_insert_post([
            'post_type' => 'quiz',
            'post_title' => $quiz->quiz_title,
            'post_status' => 'publish',
        ]);
        if ($post_id) {
            update_post_meta($post_id, '_enp_quiz_id', $quiz->quiz_id);
        }
    }
});

/**
 * Update quiz status
 */
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $quiz_posts = get_posts([
        'post_type' => 'quiz',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);
    foreach ($quiz_posts as $post_id) {
        $quiz_id = get_post_meta($post_id, '_enp_quiz_id', true);
        if (!$quiz_id) continue;

        // Get status from custom table
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT quiz_status FROM {$wpdb->prefix}enp_quiz WHERE quiz_id = %d",
            $quiz_id
        ));
        if ($status !== null) {
            update_post_meta($post_id, 'quiz_status', $status);
        }
    }
});

/**
 * Update quiz fields
 */
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $quiz_posts = get_posts([
        'post_type' => 'quiz',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);
    foreach ($quiz_posts as $post_id) {
        $quiz_id = get_post_meta($post_id, '_enp_quiz_id', true);
        if (!$quiz_id) continue;

        // Get all fields from custom table
        $quiz = $wpdb->get_row($wpdb->prepare(
            "SELECT quiz_status, quiz_owner, quiz_created_at, quiz_views FROM {$wpdb->prefix}enp_quiz WHERE quiz_id = %d",
            $quiz_id
        ));

        if ($quiz) {
            update_post_meta($post_id, 'quiz_status', $quiz->quiz_status);
            update_post_meta($post_id, 'quiz_owner', $quiz->quiz_owner);
            update_post_meta($post_id, 'quiz_created_at', $quiz->quiz_created_at);
            update_post_meta($post_id, 'quiz_views', $quiz->quiz_views);
        }

        // Get embed sites as a comma-separated list
        $sites = $wpdb->get_col($wpdb->prepare(
            "SELECT s.embed_site_url
             FROM {$wpdb->prefix}enp_embed_site s
             INNER JOIN {$wpdb->prefix}enp_embed_quiz eq ON s.embed_site_id = eq.embed_site_id
             WHERE eq.quiz_id = %d",
            $quiz_id
        ));
        if (!empty($sites)) {
            update_post_meta($post_id, 'embed_sites', implode(', ', $sites));
        } else {
            update_post_meta($post_id, 'embed_sites', '');
        }
    }
});

/**
 * Delete duplicate quiz posts
 */
add_action('admin_init', function() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;

    // Get all quiz posts with _enp_quiz_id meta, grouped by quiz_id
    $results = $wpdb->get_results("
        SELECT pm.meta_value as quiz_id, GROUP_CONCAT(pm.post_id ORDER BY pm.post_id ASC) as post_ids, COUNT(*) as count
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_enp_quiz_id'
        AND p.post_type = 'quiz'
        GROUP BY pm.meta_value
        HAVING count > 1
    ");

    $deleted = 0;
    foreach ($results as $row) {
        $post_ids = explode(',', $row->post_ids);
        // Keep the first post, delete the rest
        array_shift($post_ids);
        foreach ($post_ids as $dup_id) {
            // Move to trash (safer than permanent delete)
            wp_trash_post((int)$dup_id);
            $deleted++;
        }
    }

    if ($deleted > 0) {
        add_action('admin_notices', function() use ($deleted) {
            echo '<div class="notice notice-success is-dismissible"><p>Deleted ' . esc_html($deleted) . ' duplicate quiz posts.</p></div>';
        });
    }
});