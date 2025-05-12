<?php
/**
 * Import publications from CSV to posts
 * 
 * @param string $csv_file Path to the CSV file
 * @return array|WP_Error Array of created post IDs or WP_Error on failure
 */
function import_publications_from_csv($csv_file) {
    if (!file_exists($csv_file)) {
        return new WP_Error('file_not_found', 'CSV file not found');
    }

    $created_posts = [];
    $row = 1;
    
    if (($handle = fopen($csv_file, "r")) !== FALSE) {
        // Skip header row
        $headers = fgetcsv($handle);
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $row++;
            
            // Map CSV columns to data
            $publication = array_combine($headers, $data);
            
            // Prepare post data
            $post_data = [
                'post_title'    => $publication['title'],
                'post_type'     => 'publication',
                'post_status'   => 'publish',
                'meta_input'    => [
                    'publication_date'     => $publication['date'],
                    'publication_year_date' => $publication['year_date'],
                    'publication_url'      => $publication['url'],
                    'publication_authors'  => $publication['entry_authors'],
                    'publication_subtitle' => $publication['subtitle']
                ]
            ];
            
            // Insert the post
            $post_id = wp_insert_post($post_data);
            
            if (!is_wp_error($post_id)) {
                $created_posts[] = $post_id;
                
                // Set featured image if exists
                if (!empty($publication['picture'])) {
                    set_post_thumbnail($post_id, $publication['picture']);
                }
            } else {
                error_log("Error creating post for row {$row}: " . $post_id->get_error_message());
            }
        }
        fclose($handle);
    }
    
    return $created_posts;
}

// Example usage:
$csv_file = get_template_directory() . '/repeater-export-2025-05-07-11-18-40.csv';
$result = import_publications_from_csv($csv_file);

if (is_wp_error($result)) {
    echo "Error: " . $result->get_error_message();
} else {
    echo "Successfully created " . count($result) . " posts.";
}
