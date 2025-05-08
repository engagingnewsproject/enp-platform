<?php
/**
 * Update date format for press and publication posts
 * 
 * This script updates the publication date format for press and publication posts
 * to ensure consistency across the site.
 * 
 * Usage: wp eval-file update-dates.php
 */

// Ensure we're in WordPress context
if (!defined('ABSPATH')) {
    require_once('wp-load.php');
}

// Get all press and publication posts
$posts = get_posts(array(
    'post_type' => array('press', 'publication'),
    'numberposts' => -1,
    'post_status' => 'any'
));

echo "Found " . count($posts) . " posts to check\n";

$updated = 0;
$skipped = 0;

foreach ($posts as $post) {
    // Get the current publication date
    $date_field = $post->post_type === 'press' ? 'press_article_publication_date' : 'publication_date';
    $current_date = get_field($date_field, $post->ID);
    
    if (!$current_date) {
        echo "Skipping post {$post->ID} ({$post->post_title}) - no date set\n";
        $skipped++;
        continue;
    }
    
    // Convert the date to Ymd format if it's not already
    if (strlen($current_date) !== 8) {
        $timestamp = strtotime($current_date);
        if ($timestamp) {
            $new_date = date('Ymd', $timestamp);
            update_field($date_field, $new_date, $post->ID);
            echo "Updated post {$post->ID} ({$post->post_title}) from {$current_date} to {$new_date}\n";
            $updated++;
        } else {
            echo "Skipping post {$post->ID} ({$post->post_title}) - invalid date format: {$current_date}\n";
            $skipped++;
        }
    } else {
        echo "Skipping post {$post->ID} ({$post->post_title}) - already in correct format\n";
        $skipped++;
    }
}

echo "\nUpdate completed:\n";
echo "- {$updated} posts updated\n";
echo "- {$skipped} posts skipped\n"; 