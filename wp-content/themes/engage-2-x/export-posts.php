<?php
/**
 * Export Press and Publication posts with all metadata
 * 
 * This script exports all press and publication posts from the local site,
 * including their ACF fields, taxonomies, and featured images.
 * 
 * Usage: Place this file in your WordPress theme directory and access it via browser
 * or run via WP-CLI: wp eval-file export-posts.php
 */

// Ensure we're in WordPress context
if (!defined('ABSPATH')) {
    require_once('wp-load.php');
}

// Function to get all post data including ACF fields and taxonomies
function get_post_data($post_id) {
    $post = get_post($post_id);
    $post_data = array(
        'post_type' => $post->post_type,
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_status' => $post->post_status,
        'post_name' => $post->post_name,
        'post_date' => $post->post_date,
        'post_modified' => $post->post_modified,
        'meta' => array(),
        'taxonomies' => array(),
        'featured_image' => null
    );

    // Get all ACF fields
    $acf_fields = get_fields($post_id);
    if ($acf_fields) {
        $post_data['meta'] = $acf_fields;
    }

    // Get all taxonomies
    $taxonomies = get_object_taxonomies($post->post_type);
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'names'));
        if (!is_wp_error($terms)) {
            $post_data['taxonomies'][$taxonomy] = $terms;
        }
    }

    // Get featured image
    if (has_post_thumbnail($post_id)) {
        $image_id = get_post_thumbnail_id($post_id);
        $image_url = wp_get_attachment_url($image_id);
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        $post_data['featured_image'] = array(
            'url' => $image_url,
            'alt' => $image_alt
        );
    }

    return $post_data;
}

// Get all press and publication posts
$press_posts = get_posts(array(
    'post_type' => 'press',
    'numberposts' => -1,
    'post_status' => 'publish'
));

$publication_posts = get_posts(array(
    'post_type' => 'publication',
    'numberposts' => -1,
    'post_status' => 'publish'
));

// Combine all posts
$all_posts = array_merge($press_posts, $publication_posts);

// Export data
$export_data = array();
foreach ($all_posts as $post) {
    $export_data[] = get_post_data($post->ID);
}

// Save to JSON file
$json_file = 'posts-export-' . date('Y-m-d') . '.json';
file_put_contents($json_file, json_encode($export_data, JSON_PRETTY_PRINT));

echo "Export completed. File saved as: " . $json_file; 