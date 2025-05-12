<?php
/**
 * Import Press and Publication posts from JSON export
 * 
 * This script imports press and publication posts from a JSON export file,
 * checking for duplicates before importing.
 * 
 * Usage: 
 * - Normal run: wp eval-file import-posts.php
 * - Dry run (no changes): wp eval-file import-posts.php dry-run
 */

// Ensure we're in WordPress context
if (!defined('ABSPATH')) {
    require_once('wp-load.php');
}

// Check for dry run mode
$is_dry_run = false;
if (isset($argv[1]) && $argv[1] === 'dry-run') {
    $is_dry_run = true;
    echo "Running in DRY RUN mode - no changes will be made\n\n";
}

// Function to check if a post already exists
function check_post_exists($post_data) {
    $args = array(
        'post_type' => $post_data['post_type'],
        'post_status' => 'any',
        'posts_per_page' => 1,
        'meta_query' => array()
    );

    // Check by title
    $args['title'] = $post_data['post_title'];

    // Add meta query for unique identifiers if they exist
    if ($post_data['post_type'] === 'press' && isset($post_data['meta']['press_article_url'])) {
        $args['meta_query'][] = array(
            'key' => 'press_article_url',
            'value' => $post_data['meta']['press_article_url']
        );
    } elseif ($post_data['post_type'] === 'publication' && isset($post_data['meta']['publication_url'])) {
        $args['meta_query'][] = array(
            'key' => 'publication_url',
            'value' => $post_data['meta']['publication_url']
        );
    }

    $query = new WP_Query($args);
    return $query->have_posts() ? $query->posts[0]->ID : false;
}

// Function to import a post
function import_post($post_data, $is_dry_run = false) {
    // Check if post already exists
    $existing_post_id = check_post_exists($post_data);
    if ($existing_post_id) {
        echo "Post '{$post_data['post_title']}' already exists (ID: $existing_post_id). Skipping...\n";
        return false;
    }

    if ($is_dry_run) {
        echo "Would import post '{$post_data['post_title']}'\n";
        return true;
    }

    // Create new post
    $post_args = array(
        'post_type' => $post_data['post_type'],
        'post_title' => $post_data['post_title'],
        'post_content' => $post_data['post_content'],
        'post_status' => $post_data['post_status'],
        'post_name' => $post_data['post_name'],
        'post_date' => $post_data['post_date'],
        'post_modified' => $post_data['post_modified']
    );

    $post_id = wp_insert_post($post_args);

    if (is_wp_error($post_id)) {
        echo "Error creating post '{$post_data['post_title']}': " . $post_id->get_error_message() . "\n";
        return false;
    }

    // Set taxonomies
    foreach ($post_data['taxonomies'] as $taxonomy => $terms) {
        wp_set_object_terms($post_id, $terms, $taxonomy);
    }

    // Set ACF fields
    if (!empty($post_data['meta'])) {
        foreach ($post_data['meta'] as $key => $value) {
            update_field($key, $value, $post_id);
        }
    }

    // Set featured image if exists
    if (!empty($post_data['featured_image'])) {
        $image_url = $post_data['featured_image']['url'];
        $image_alt = $post_data['featured_image']['alt'];

        // Download and attach the image
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        $filename = basename($image_url);

        if ($image_data) {
            $file = $upload_dir['path'] . '/' . $filename;
            file_put_contents($file, $image_data);

            $wp_filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $file, $post_id);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $file);
            wp_update_attachment_metadata($attach_id, $attach_data);
            set_post_thumbnail($post_id, $attach_id);

            // Set image alt text
            update_post_meta($attach_id, '_wp_attachment_image_alt', $image_alt);
        }
    }

    echo "Successfully imported post '{$post_data['post_title']}' (ID: $post_id)\n";
    return true;
}

// Get the most recent export file
$export_files = glob('posts-export-*.json');
if (empty($export_files)) {
    die("No export files found.\n");
}

// Sort files by date (newest first)
usort($export_files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

$latest_export = $export_files[0];
$import_data = json_decode(file_get_contents($latest_export), true);

if (empty($import_data)) {
    die("No data found in export file.\n");
}

// Import posts
$imported = 0;
$skipped = 0;

foreach ($import_data as $post_data) {
    if (import_post($post_data, $is_dry_run)) {
        $imported++;
    } else {
        $skipped++;
    }
}

echo "\nImport completed:\n";
echo "Imported: $imported posts\n";
echo "Skipped: $skipped posts\n";

if ($is_dry_run) {
    echo "\nThis was a DRY RUN - no changes were made to the database.\n";
    echo "To perform the actual import, run the script without the dry-run parameter.\n";
} 