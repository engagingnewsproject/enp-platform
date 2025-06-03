<?php
/**
 * Plugin Name: Mark Suspicious Users as Spam
 * Description: Updates users from a CSV to the "spam_user" role instead of deleting them.
 */

add_action('init', function () {
    if (!is_admin()) return;

    $csv_path = wp_upload_dir()['basedir'] . '/suspicious-users-no-quizzes.csv';

    if (!file_exists($csv_path)) {
        error_log("CSV file not found: " . $csv_path);
        return;
    }

    $handle = fopen($csv_path, 'r');
    if (!$handle) return;

    $first = true;
    while (($row = fgetcsv($handle)) !== false) {
        if ($first) {
            $first = false; // skip header
            continue;
        }

        $user_id = intval($row[0]);

        $user = get_user_by('ID', $user_id);
        if ($user) {
            $user->set_role('spam_user');
            error_log("Set user ID $user_id ({$user->user_email}) to spam_user");
        }
    }

    fclose($handle);

    // Optional: remove plugin after run
    $self = __FILE__;
    register_shutdown_function(function () use ($self) {
        // unlink($self);
    });
});
