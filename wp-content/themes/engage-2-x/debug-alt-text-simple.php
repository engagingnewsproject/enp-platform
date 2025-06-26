<?php
/**
 * Simple Alt Text Debug - Works with WP Engine
 * 
 * @package Engage
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Set up minimal WordPress environment
define('WP_USE_THEMES', false);
define('SHORTINIT', true);

// Load WordPress core
require_once __DIR__ . '/../../../wp-load.php';

echo "=== Alt Text Generator Debug (Simple) ===\n\n";

global $wpdb;

// Check 1: Total images
$query_total = "
    SELECT COUNT(*) as count
    FROM {$wpdb->posts} p
    WHERE p.post_type = 'attachment' 
    AND p.post_mime_type LIKE 'image/%'
";

$total_result = $wpdb->get_row($query_total);
echo "1. Total images in database: " . number_format($total_result->count) . "\n";

// Check 2: Images with alt text
$query_with_alt = "
    SELECT COUNT(*) as count
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
    WHERE p.post_type = 'attachment' 
    AND p.post_mime_type LIKE 'image/%'
    AND pm.meta_key = '_wp_attachment_image_alt'
    AND pm.meta_value != ''
";

$with_alt_result = $wpdb->get_row($query_with_alt);
echo "2. Images with alt text: " . number_format($with_alt_result->count) . "\n";

// Check 3: Images without alt text
$query_without_alt = "
    SELECT COUNT(*) as count
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
    WHERE p.post_type = 'attachment' 
    AND p.post_mime_type LIKE 'image/%'
    AND (pm.meta_value IS NULL OR pm.meta_value = '')
";

$without_alt_result = $wpdb->get_row($query_without_alt);
echo "3. Images without alt text: " . number_format($without_alt_result->count) . "\n";

// Check 4: Sample images without alt text
$query_sample = "
    SELECT p.ID, p.post_title, p.post_name, p.post_mime_type
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
    WHERE p.post_type = 'attachment' 
    AND p.post_mime_type LIKE 'image/%'
    AND (pm.meta_value IS NULL OR pm.meta_value = '')
    ORDER BY p.post_date DESC
    LIMIT 5
";

$sample_results = $wpdb->get_results($query_sample);

echo "\n4. Sample images without alt text:\n";
if (empty($sample_results)) {
    echo "   No images found without alt text!\n";
} else {
    foreach ($sample_results as $image) {
        echo "   - ID: {$image->ID}, Title: {$image->post_title}, Type: {$image->post_mime_type}\n";
    }
}

// Check 5: API key configuration
$api_key = get_option('engage_alt_text_openai_key', '');
echo "\n5. OpenAI API key configured: " . (!empty($api_key) ? 'Yes' : 'No') . "\n";
if (!empty($api_key)) {
    echo "   API key starts with: " . substr($api_key, 0, 10) . "...\n";
}

// Check 6: Recent alt text generation attempts
$query_recent = "
    SELECT p.ID, p.post_title, p.post_modified, pm.meta_value as alt_text
    FROM {$wpdb->posts} p
    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
    WHERE p.post_type = 'attachment' 
    AND p.post_mime_type LIKE 'image/%'
    AND pm.meta_key = '_wp_attachment_image_alt'
    AND pm.meta_value != ''
    ORDER BY p.post_modified DESC
    LIMIT 3
";

$recent_results = $wpdb->get_results($query_recent);

echo "\n6. Recently modified images with alt text:\n";
if (empty($recent_results)) {
    echo "   No recently modified images with alt text found.\n";
} else {
    foreach ($recent_results as $image) {
        echo "   - ID: {$image->ID}, Title: {$image->post_title}, Modified: {$image->post_modified}\n";
        echo "     Alt text: \"{$image->alt_text}\"\n";
    }
}

echo "\n=== Debug Complete ===\n"; 