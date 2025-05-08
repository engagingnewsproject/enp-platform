<?php
/**
 * Script to update dates to Ymd format for press posts
 * 
 * This script will:
 * 1. Get all press posts
 * 2. Check their press_article_publication_date field
 * 3. Convert any non-Ymd dates to Ymd format
 * 4. Update the post meta
 */

// Load WordPress
require_once('wp-load.php');

// Get all press posts
$args = [
    'post_type' => 'press',
    'posts_per_page' => -1,
    'post_status' => 'any',
];

$posts = get_posts($args);
$updated = 0;
$skipped = 0;
$errors = 0;

echo "Starting press date update...\n";

foreach ($posts as $post) {
    $date = get_post_meta($post->ID, 'press_article_publication_date', true);
    
    // Skip if no date
    if (!$date) {
        $skipped++;
        continue;
    }
    
    // Skip if already in Ymd format
    if (preg_match('/^\d{8}$/', $date)) {
        $skipped++;
        continue;
    }
    
    // Try to convert the date
    $timestamp = strtotime($date);
    if ($timestamp) {
        $new_date = date('Ymd', $timestamp);
        update_post_meta($post->ID, 'press_article_publication_date', $new_date);
        echo "Updated press {$post->ID}: {$date} -> {$new_date}\n";
        $updated++;
    } else {
        echo "Error converting date for press {$post->ID}: {$date}\n";
        $errors++;
    }
}

echo "\nUpdate complete!\n";
echo "Updated: {$updated}\n";
echo "Skipped: {$skipped}\n";
echo "Errors: {$errors}\n"; 