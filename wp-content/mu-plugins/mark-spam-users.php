<?php
/**
 * Plugin Name: Mark Suspicious Users (On-Demand)
 * Description: Updates users from a CSV to the "spam_user" role, only when ?run_spam_flagger=1 is in the URL.
 */

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) return;

    // Only run when this URL param is present
    if (!isset($_GET['run_spam_flagger']) || $_GET['run_spam_flagger'] !== '1') return;

    $csv_path = wp_upload_dir()['basedir'] . '/suspicious-users-no-quizzes.csv';

    if (!file_exists($csv_path)) {
        wp_die("CSV file not found at: $csv_path");
    }

    $handle = fopen($csv_path, 'r');
    if (!$handle) {
        wp_die("Unable to open the CSV file.");
    }

    $count = 0;
    $first = true;

    while (($row = fgetcsv($handle)) !== false) {
        if ($first) {
            $first = false; // skip header row
            continue;
        }

        $user_id = intval($row[0]);
        $user = get_user_by('ID', $user_id);
        if ($user) {
            $user->set_role('spam_user');
            $count++;
        }
    }

    fclose($handle);

    wp_die("Marked {$count} users as spam_user.");
});
