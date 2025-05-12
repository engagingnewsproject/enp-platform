<?php
/**
 * Export ACF repeater field content to posts
 * 
 * @param int $page_id The ID of the page containing the repeater field
 * @param string $repeater_field_name The name of the repeater field
 * @param string $post_type The post type to create
 * @param array $field_mapping Array mapping repeater sub-fields to post fields
 * @return array Array of created post IDs
 */
function export_repeater_to_posts($page_id, $repeater_field_name, $post_type, $field_mapping) {
    // Get repeater field values
    $repeater_rows = get_field($repeater_field_name, $page_id);
    
    if (!$repeater_rows || !is_array($repeater_rows)) {
        return [];
    }
    
    $created_posts = [];
    
    foreach ($repeater_rows as $row) {
        // Prepare post data
        $post_data = [
            'post_type' => $post_type,
            'post_status' => 'publish',
        ];
        
        // Map repeater fields to post fields
        foreach ($field_mapping as $repeater_field => $post_field) {
            if (isset($row[$repeater_field])) {
                if ($post_field === 'post_title') {
                    $post_data['post_title'] = $row[$repeater_field];
                } elseif ($post_field === 'post_content') {
                    $post_data['post_content'] = $row[$repeater_field];
                } elseif ($post_field === '_thumbnail_id') {
                    // Handle featured image separately
                    if (is_numeric($row[$repeater_field])) {
                        $post_data['meta_input']['_thumbnail_id'] = $row[$repeater_field];
                    }
                } else {
                    // Handle ACF fields
                    $post_data['meta_input'][$post_field] = $row[$repeater_field];
                }
            }
        }
        
        // Insert the post
        $post_id = wp_insert_post($post_data);
        
        if (!is_wp_error($post_id)) {
            $created_posts[] = $post_id;
        }
    }
    
    return $created_posts;
}

// Example usage:

$page_id = 17069; // Replace with your page ID
$repeater_field_name = 'publication'; // Replace with your repeater field name
$post_type = 'publications'; // Replace with your target post type

$field_mapping = [
    'title' => 'post_title',
    'picture' => '_thumbnail_id',
    'date' => 'publication_date',
	'year_date' => 'publication_year_date',
    'url' => 'publication_url',
	'entry_authors' => 'publication_authors',
	'subtitle' => 'publication_subtitle',
];

$created_posts = export_repeater_to_posts($page_id, $repeater_field_name, $post_type, $field_mapping);

