<?php
// Load WordPress environment
$wp_load_path = dirname(__DIR__, 3) . '/wp-load.php';

if (file_exists($wp_load_path)) {
	require_once($wp_load_path);
} else {
	die("Error: wp-load.php not found at $wp_load_path");
}

require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

// Path to the CSV file
$csv_file = get_template_directory() . '/import-publications-sci-com.csv';

if (!file_exists($csv_file)) {
	die("CSV file not found!");
}

// Open CSV file
$file = fopen($csv_file, 'r');
$header = fgetcsv($file); // Read header row

// Temporarily disable problematic theme hooks
remove_all_actions('save_post_publication');

while (($row = fgetcsv($file, 1000, ",")) !== false) { // Explicitly set delimiter
	error_log("Processing new row...");

	// Ensure we have enough columns
	if (count($row) < 7) {
		error_log("Skipping row due to missing columns: " . print_r($row, true));
		continue;
	}
	// Insert new 'publication' post
	$post_id = wp_insert_post([
		'post_title'   => sanitize_text_field($row[0]), // Title
		'post_status'  => 'publish',
		'post_type'    => 'publication',
	]);

	if (is_wp_error($post_id) || !$post_id) {
		error_log("Failed to insert post: " . print_r($row[0], true));
		continue;
	}

	error_log("Created post ID: $post_id");

	$acf_fields = [
		'publication_authors' => is_array($row[1]) ? implode(", ", $row[1]) : sanitize_text_field($row[1]),
		'publication_date' => sanitize_text_field($row[3]),
		'publication_year_date' => ($row[2] === 'Yes') ? 1 : 0,
		'publication_url' => esc_url($row[4]),
		'publication_subtitle' => sanitize_text_field($row[5]),
	];

	foreach ($acf_fields as $field_key => $field_value) {
		if (!empty($field_value)) {
			$update_status = update_field($field_key, $field_value, $post_id);
			if (!$update_status) {
				error_log("⚠️ Failed to update ACF field: $field_key for post ID: $post_id");
			} else {
				error_log("✅ ACF field updated: $field_key = $field_value for post ID: $post_id");
			}
		}
	}


	// Handle Image Import
	if (!empty($row[6])) {
		global $wpdb;

		// Get the attachment ID based on the file URL
		$image_url = esc_url($row[6]);
		error_log("Checking for image URL in database: " . $image_url);

		$image_id = $wpdb->get_var($wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment'",
			$image_url
		));

		if ($image_id) {
			error_log("✅ Image Found: ID $image_id for URL: $image_url");

			// Ensure WordPress knows this post exists before setting thumbnail
			wp_update_post(['ID' => $post_id, 'post_type' => 'publication']);

			// Attempt to set featured image
			$thumbnail_set = set_post_thumbnail($post_id, $image_id);

			if ($thumbnail_set) {
				error_log("🎉 Successfully set featured image for post ID: $post_id");
			} else {
				error_log("⚠️ Failed to set post thumbnail for post ID: $post_id");
			}
		} else {
			error_log("⚠️ No existing image found in database for: " . $image_url);
		}
	}



	error_log("Row processed successfully.");
}

// Re-enable hooks after import
add_all_actions('save_post_publication');

fclose($file);
echo "Import complete!";
