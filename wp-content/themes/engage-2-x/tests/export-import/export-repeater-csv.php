<?php
/**
 * Export ACF repeater field content to CSV
 * 
 * @param int $page_id The ID of the page containing the repeater field
 * @param string $repeater_field_name The name of the repeater field
 * @return string Path to the created CSV file
 */
function export_repeater_to_csv($page_id, $repeater_field_name) {
    // Get repeater field values
    $repeater_rows = get_field($repeater_field_name, $page_id);
    
    if (!$repeater_rows || !is_array($repeater_rows)) {
        return false;
    }
    
    // Create CSV file in the uploads directory
    $upload_dir = wp_upload_dir();
    $csv_file = $upload_dir['path'] . '/repeater-export-' . date('Y-m-d-H-i-s') . '.csv';
    
    // Open file for writing
    $fp = fopen($csv_file, 'w');
    
    // Get headers from first row
    if (!empty($repeater_rows)) {
        $headers = array_keys($repeater_rows[0]);
        fputcsv($fp, $headers);
    }
    
    // Write data rows
    foreach ($repeater_rows as $row) {
        // Convert any arrays to strings
        $row_data = array_map(function($value) {
            if (is_array($value)) {
                return implode(', ', $value);
            }
            return $value;
        }, $row);
        
        fputcsv($fp, $row_data);
    }
    
    fclose($fp);
    
    return $csv_file;
}

// Run the export
$page_id = 17069;
$repeater_field_name = 'publication';

$csv_file = export_repeater_to_csv($page_id, $repeater_field_name);

if ($csv_file) {
    echo "CSV file created successfully at: " . $csv_file;
} else {
    echo "No data found or error occurred.";
}
