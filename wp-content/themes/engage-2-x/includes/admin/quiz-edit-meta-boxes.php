<?php
/**
 * Read-only ENP Quiz data on the quiz CPT edit screen.
 *
 * @package Engage
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register meta boxes for the quiz post type edit screen.
 *
 * @return void
 */
function engage_quiz_edit_register_meta_boxes(): void {
    add_meta_box(
        'engage_quiz_enp_details',
        __('Quiz details (ENP)', 'engage'),
        'engage_quiz_edit_render_details_meta_box',
        'quiz',
        'normal',
        'high'
    );

    add_meta_box(
        'engage_quiz_enp_questions_embeds',
        __('Questions & embed sites', 'engage'),
        'engage_quiz_edit_render_questions_embeds_meta_box',
        'quiz',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes_quiz', 'engage_quiz_edit_register_meta_boxes');

/**
 * Scoped admin styles for quiz edit meta boxes.
 *
 * @return void
 */
function engage_quiz_edit_admin_styles(): void {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'quiz') {
        return;
    }
    echo '<style>
        .engage-quiz-edit-meta .engage-quiz-edit-dl { margin: 0; }
        .engage-quiz-edit-meta .engage-quiz-edit-dl dt { font-weight: 600; margin-top: 10px; }
        .engage-quiz-edit-meta .engage-quiz-edit-dl dd { margin: 4px 0 0 0; }
        .engage-quiz-edit-meta .engage-quiz-edit-rich {
            max-height: 200px;
            overflow: auto;
            padding: 8px;
            background: #f6f7f7;
            border: 1px solid #c3c4c7;
        }
        .engage-quiz-edit-meta .engage-quiz-edit-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .engage-quiz-edit-meta .engage-quiz-edit-table th,
        .engage-quiz-edit-meta .engage-quiz-edit-table td {
            border: 1px solid #c3c4c7;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        .engage-quiz-edit-meta .engage-quiz-edit-table th { background: #f0f0f1; width: 28%; }
        .engage-quiz-edit-meta ol.engage-quiz-question-list { margin: 8px 0 0 1.2em; }
    </style>';
}
add_action('admin_head', 'engage_quiz_edit_admin_styles');

/**
 * Load ENP quiz row and resolve UI state for the edit screen.
 *
 * @param int $post_id WordPress post ID.
 * @return array{state:string, quiz_id:?int, row:?object} state is no_meta|not_found|deleted|ok.
 */
function engage_quiz_edit_get_enp_context(int $post_id): array {
    $raw = get_post_meta($post_id, '_enp_quiz_id', true);
    if ($raw === '' || $raw === null) {
        return array(
            'state'   => 'no_meta',
            'quiz_id' => null,
            'row'     => null,
        );
    }

    $quiz_id = (int) $raw;
    if ($quiz_id < 1) {
        return array(
            'state'   => 'no_meta',
            'quiz_id' => null,
            'row'     => null,
        );
    }

    global $wpdb;
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}enp_quiz WHERE quiz_id = %d",
            $quiz_id
        )
    );

    if (!$row) {
        return array(
            'state'   => 'not_found',
            'quiz_id' => $quiz_id,
            'row'     => null,
        );
    }

    if ((int) $row->quiz_is_deleted !== 0) {
        return array(
            'state'   => 'deleted',
            'quiz_id' => $quiz_id,
            'row'     => $row,
        );
    }

    return array(
        'state'   => 'ok',
        'quiz_id' => $quiz_id,
        'row'     => $row,
    );
}

/**
 * Render admin notice for non-ok ENP context.
 *
 * @param array $context From engage_quiz_edit_get_enp_context().
 * @return void
 */
function engage_quiz_edit_render_context_notice(array $context): void {
    if ($context['state'] === 'ok') {
        return;
    }

    if ($context['state'] === 'no_meta') {
        $list_url = admin_url('edit.php?post_type=quiz');
        echo '<p class="notice notice-warning inline"><strong>' . esc_html__('Not linked to ENP Quiz', 'engage') . '</strong> — ';
        echo esc_html__(
            'This post has no ENP quiz ID. Run Sync Quizzes from the Quizzes list screen to link it.',
            'engage'
        );
        echo ' <a href="' . esc_url($list_url) . '">' . esc_html__('Go to Quizzes list', 'engage') . '</a></p>';
        return;
    }

    if ($context['state'] === 'not_found') {
        echo '<p class="notice notice-error inline"><strong>' . esc_html__('Quiz not found', 'engage') . '</strong> — ';
        echo esc_html__(
            'No row exists in the ENP quiz table for this ID.',
            'engage'
        );
        echo '</p>';
        return;
    }

    if ($context['state'] === 'deleted') {
        echo '<p class="notice notice-error inline"><strong>' . esc_html__('Quiz deleted in ENP', 'engage') . '</strong> — ';
        echo esc_html__(
            'This quiz is marked deleted in the ENP database.',
            'engage'
        );
        echo '</p>';
    }
}

/**
 * Output a definition list row (dt + dd).
 *
 * @param string $label Row label.
 * @param string $html  Escaped HTML content for dd.
 * @return void
 */
function engage_quiz_edit_dl_row(string $label, string $html): void {
    echo '<dt>' . esc_html($label) . '</dt><dd>' . $html . '</dd>';
}

/**
 * Meta box: quiz row, options, stats, rich text fields, external links.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function engage_quiz_edit_render_details_meta_box(WP_Post $post): void {
    if (!current_user_can('edit_post', $post->ID)) {
        return;
    }

    $context = engage_quiz_edit_get_enp_context((int) $post->ID);
    echo '<div class="engage-quiz-edit-meta">';

    engage_quiz_edit_render_context_notice($context);

    if ($context['state'] !== 'ok' || !$context['row']) {
        echo '</div>';
        return;
    }

    $quiz_id = (int) $context['quiz_id'];
    $row     = $context['row'];

    global $wpdb;

    if (defined('ENP_QUIZ_CREATE_URL')) {
        $create_url = ENP_QUIZ_CREATE_URL . $quiz_id . '/';
        echo '<p><a class="button button-secondary" href="' . esc_url($create_url) . '" target="_blank" rel="noopener noreferrer">';
        echo esc_html__('Open in Quiz Creator', 'engage');
        echo '</a> ';
        echo '<span class="description">' . esc_html__('Requires ENP permissions and login on the front end.', 'engage') . '</span></p>';
    }

    $take_url = home_url('quiz-embed/' . $quiz_id . '/');
    echo '<p><a class="button button-secondary" href="' . esc_url($take_url) . '" target="_blank" rel="noopener noreferrer">';
    echo esc_html__('Open public quiz (embed URL)', 'engage');
    echo '</a></p>';

    echo '<dl class="engage-quiz-edit-dl">';

    engage_quiz_edit_dl_row(__('ENP quiz ID', 'engage'), esc_html((string) $quiz_id));
    engage_quiz_edit_dl_row(__('Status', 'engage'), esc_html((string) $row->quiz_status));

    $owner_id = (int) $row->quiz_owner;
    $owner    = $owner_id ? get_user_by('id', $owner_id) : false;
    if ($owner) {
        $link = admin_url('user-edit.php?user_id=' . $owner_id);
        engage_quiz_edit_dl_row(
            __('Owner', 'engage'),
            '<a href="' . esc_url($link) . '">' . esc_html($owner->user_login) . '</a>'
        );
    } else {
        engage_quiz_edit_dl_row(__('Owner (user ID)', 'engage'), esc_html((string) $owner_id));
    }

    engage_quiz_edit_dl_row(__('Created at', 'engage'), esc_html((string) $row->quiz_created_at));
    engage_quiz_edit_dl_row(__('Updated at', 'engage'), esc_html((string) $row->quiz_updated_at));
    engage_quiz_edit_dl_row(__('Created by (user ID)', 'engage'), esc_html((string) $row->quiz_created_by));
    engage_quiz_edit_dl_row(__('Updated by (user ID)', 'engage'), esc_html((string) $row->quiz_updated_by));

    engage_quiz_edit_dl_row(__('Views', 'engage'), esc_html((string) $row->quiz_views));
    engage_quiz_edit_dl_row(__('Starts', 'engage'), esc_html((string) $row->quiz_starts));
    engage_quiz_edit_dl_row(__('Finishes', 'engage'), esc_html((string) $row->quiz_finishes));
    engage_quiz_edit_dl_row(__('Score average', 'engage'), esc_html((string) $row->quiz_score_average));
    engage_quiz_edit_dl_row(__('Time spent', 'engage'), esc_html((string) $row->quiz_time_spent));
    engage_quiz_edit_dl_row(__('Time spent average', 'engage'), esc_html((string) $row->quiz_time_spent_average));

    echo '</dl>';

    echo '<h4>' . esc_html__('Feedback', 'engage') . '</h4>';
    echo '<div class="engage-quiz-edit-rich">' . wp_kses_post((string) $row->quiz_feedback) . '</div>';

    echo '<h4>' . esc_html__('Finish message', 'engage') . '</h4>';
    echo '<div class="engage-quiz-edit-rich">' . wp_kses_post((string) $row->quiz_finish_message) . '</div>';

    $options = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT quiz_option_name, quiz_option_value
             FROM {$wpdb->prefix}enp_quiz_option
             WHERE quiz_id = %d
             ORDER BY quiz_option_name ASC",
            $quiz_id
        )
    );

    if (!empty($options)) {
        echo '<h4>' . esc_html__('Quiz options', 'engage') . '</h4>';
        echo '<table class="engage-quiz-edit-table"><thead><tr>';
        echo '<th>' . esc_html__('Name', 'engage') . '</th><th>' . esc_html__('Value', 'engage') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($options as $opt) {
            echo '<tr><td>' . esc_html((string) $opt->quiz_option_name) . '</td><td>';
            echo '<code style="white-space:pre-wrap;word-break:break-word;">' . esc_html((string) $opt->quiz_option_value) . '</code>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}

/**
 * Meta box: questions list and embed site URLs.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function engage_quiz_edit_render_questions_embeds_meta_box(WP_Post $post): void {
    if (!current_user_can('edit_post', $post->ID)) {
        return;
    }

    $context = engage_quiz_edit_get_enp_context((int) $post->ID);
    echo '<div class="engage-quiz-edit-meta">';

    if ($context['state'] !== 'ok') {
        engage_quiz_edit_render_context_notice($context);
        echo '</div>';
        return;
    }

    $quiz_id = (int) $context['quiz_id'];
    global $wpdb;

    $questions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT question_id, question_order, question_type, question_title
             FROM {$wpdb->prefix}enp_question
             WHERE quiz_id = %d AND question_is_deleted = 0
             ORDER BY question_order ASC, question_id ASC",
            $quiz_id
        )
    );

    echo '<h4>' . esc_html__('Questions', 'engage') . '</h4>';
    if (empty($questions)) {
        echo '<p>' . esc_html__('No questions found.', 'engage') . '</p>';
    } else {
        echo '<ol class="engage-quiz-question-list">';
        foreach ($questions as $q) {
            $title = wp_strip_all_tags((string) $q->question_title);
            echo '<li><strong>' . esc_html((string) $q->question_type) . '</strong> ';
            echo '<span class="description">#' . esc_html((string) $q->question_id) . ' · ' . esc_html__('order', 'engage') . ' ' . esc_html((string) $q->question_order) . '</span><br>';
            echo esc_html(wp_html_excerpt($title, 200, '…')) . '</li>';
        }
        echo '</ol>';
    }

    $sites = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT s.embed_site_url
             FROM {$wpdb->prefix}enp_embed_site s
             INNER JOIN {$wpdb->prefix}enp_embed_quiz eq ON s.embed_site_id = eq.embed_site_id
             WHERE eq.quiz_id = %d
             ORDER BY s.embed_site_url ASC",
            $quiz_id
        )
    );

    echo '<h4>' . esc_html__('Embed sites', 'engage') . '</h4>';
    if (empty($sites)) {
        echo '<p>' . esc_html__('No embed sites linked.', 'engage') . '</p>';
    } else {
        echo '<ul style="margin-left:1.2em;">';
        foreach ($sites as $site) {
            $url = (string) $site->embed_site_url;
            echo '<li><a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($url) . '</a></li>';
        }
        echo '</ul>';
    }

    echo '</div>';
}
