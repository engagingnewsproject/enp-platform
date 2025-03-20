<?php
// Define the path to wp-load.php dynamically
$wp_load_path = dirname(__DIR__, 3) . '/wp-load.php';

// Check if the file exists before requiring it
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die("Error: wp-load.php not found at $wp_load_path");
}

// Set the page ID where the repeater field exists
$page_id = 14779; // Change to the actual Page ID

// Get repeater field data
$repeater_field = 'publication'; // Change to your actual repeater field name
$repeater_data = get_field($repeater_field, $page_id);

// Set headers for CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="publications.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Title', 'Authors', 'YearDate', 'Date', 'URL', 'Subtitle', 'Image']); // Adjust columns as needed


// Loop through repeater rows and export to CSV
if ($repeater_data) {
	foreach ($repeater_data as $row) {
		$image_url = (!empty($row['picture']) && is_array($row['picture'])) ? $row['picture']['url'] : '';
		
        // Debugging
        error_log("Image Data: " . print_r($row['picture'], true));
        error_log("Extracted Image URL: " . print_r($image_url, true));

        fputcsv($output, [
            $row['title'],    // Change to your actual sub-field keys
            $row['entry_authors'],
            $row['year_date'] ? 'Yes' : 'No', // Convert True/False to text
            $row['date'],
            $row['url'],
            $row['subtitle'],
            $image_url,  // Now correctly extracted
        ]);
    }
}

fclose($output);
exit;
