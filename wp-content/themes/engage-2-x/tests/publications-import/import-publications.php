<?php
// Check if running via WP-CLI
if (!defined('WP_CLI') || !WP_CLI) {
    die('This script must be run via WP-CLI');
}

/**
 * Import publications from ACF CSV export
 */
class PublicationImporter {
    private $csv_file;
    private $field_mappings = [
        'publication_%d_title' => 'title',
        'publication_%d_url' => 'url',
        'publication_%d_picture' => 'picture',
        'publication_%d_entry_authors' => 'authors',
        'publication_%d_subtitle' => 'subtitle',
        'publication_%d_date' => 'date',
        'publication_%d_year_date' => 'year_date'
    ];

    public function __construct($csv_file) {
        $this->csv_file = $csv_file;
    }

    public function import() {
        WP_CLI::log("Starting import from {$this->csv_file}...");

        // Read CSV file
        $handle = fopen($this->csv_file, 'r');
        if (!$handle) {
            WP_CLI::error("Could not open file: {$this->csv_file}");
            return;
        }

        // Skip header row
        fgetcsv($handle);
        
        $publications = [];
        
        // Process each row
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) < 2) continue;
            
            $meta_key = $data[0];
            $meta_value = $data[1];

            // Skip rows that start with underscore (ACF field keys)
            if (strpos($meta_key, '_') === 0) continue;

            // Extract publication index and field type
            foreach ($this->field_mappings as $pattern => $field) {
                for ($i = 0; $i <= 100; $i++) {
                    $field_name = sprintf($pattern, $i);
                    if ($meta_key === $field_name) {
                        if (!isset($publications[$i])) {
                            $publications[$i] = [];
                        }
                        $publications[$i][$field] = $meta_value;
                        WP_CLI::log("Found $field for publication $i: $meta_value");
                        break 2;
                    }
                }
            }
        }

        fclose($handle);

        // Create posts for each publication
        $created_count = 0;
        foreach ($publications as $index => $pub) {
            if (empty($pub['title'])) continue;

            // Create post
            $post_data = [
                'post_title' => wp_strip_all_tags($pub['title']),
                'post_type' => 'publication',
                'post_status' => 'publish'
            ];

            $post_id = wp_insert_post($post_data);

            if (is_wp_error($post_id)) {
                WP_CLI::warning("Failed to create post for: {$pub['title']}");
                continue;
            }

            // Store URL as post meta
            if (!empty($pub['url'])) {
                update_post_meta($post_id, '_publication_url', esc_url_raw($pub['url']));
            }

            // Store date fields
            if (!empty($pub['date'])) {
                // Store the original ACF date format
                update_post_meta($post_id, '_publication_date', sanitize_text_field($pub['date']));
                
                // Convert YYYYMMDD to Y-m-d format for WordPress
                $date = DateTime::createFromFormat('Ymd', $pub['date']);
                if ($date) {
                    update_post_meta($post_id, '_publication_formatted_date', $date->format('Y-m-d'));
                }
            }

            // Store year_date flag
            if (isset($pub['year_date'])) {
                update_post_meta($post_id, '_publication_year_date', absint($pub['year_date']));
            }

            // Handle featured image if present
            if (!empty($pub['picture'])) {
                set_post_thumbnail($post_id, $pub['picture']);
            }

            // Store additional meta fields
            if (!empty($pub['authors'])) {
                update_post_meta($post_id, '_publication_authors', sanitize_text_field($pub['authors']));
            }
            if (!empty($pub['subtitle'])) {
                update_post_meta($post_id, '_publication_subtitle', sanitize_text_field($pub['subtitle']));
            }

            $created_count++;
            WP_CLI::log("Created publication: {$pub['title']}");
        }

        WP_CLI::success("Import complete! Created $created_count publications.");
    }
}

// Register WP-CLI command
if (defined('WP_CLI')) {
    WP_CLI::add_command('import-publications', function($args) {
        if (empty($args[0])) {
            WP_CLI::error('Please provide the path to the CSV file');
            return;
        }

        $importer = new PublicationImporter($args[0]);
        $importer->import();
    });
}