<?php
/**
 * Check Alt Text Generation Results
 * 
 * Simple script to view recently generated alt text
 * 
 * @package Engage
 */

// Load WordPress
require_once __DIR__ . '/../../../wp-config.php';

// Prevent direct web access
if (php_sapi_name() !== 'cli' && !current_user_can('manage_options')) {
    die('Unauthorized access');
}

global $wpdb;

echo "=== Alt Text Generation Results ===\n\n";

// Get recently processed images (with alt text)
$query = "
    SELECT 
        p.ID,
        p.post_title,
        p.post_date,
        pm.meta_value as alt_text
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
    WHERE p.post_type = 'attachment' 
    AND p.post_mime_type LIKE 'image/%'
    AND pm.meta_key = '_wp_attachment_image_alt'
    AND pm.meta_value != ''
    ORDER BY p.post_modified DESC
    LIMIT 10
";

$results = $wpdb->get_results($query);

if (empty($results)) {
    echo "No images with alt text found.\n";
} else {
    echo "Recently processed images with alt text:\n\n";
    
    foreach ($results as $image) {
        echo "ID: {$image->ID}\n";
        echo "Title: {$image->post_title}\n";
        echo "Date: {$image->post_date}\n";
        echo "Alt Text: {$image->alt_text}\n";
        echo "---\n";
    }
}

// Get count of images still without alt text
$query_no_alt = "
    SELECT COUNT(*) as count
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
    WHERE p.post_type = 'attachment' 
    AND p.post_mime_type LIKE 'image/%'
    AND (pm.meta_value IS NULL OR pm.meta_value = '')
";

$count_result = $wpdb->get_row($query_no_alt);

echo "\n=== Summary ===\n";
echo "Images still without alt text: {$count_result->count}\n";

// Get total count of images
$query_total = "
    SELECT COUNT(*) as count
    FROM {$wpdb->posts} p
    WHERE p.post_type = 'attachment' 
    AND p.post_mime_type LIKE 'image/%'
";

$total_result = $wpdb->get_row($query_total);
$with_alt = $total_result->count - $count_result->count;

echo "Images with alt text: {$with_alt}\n";
echo "Total images: {$total_result->count}\n";
echo "Progress: " . round(($with_alt / $total_result->count) * 100, 1) . "%\n"; 