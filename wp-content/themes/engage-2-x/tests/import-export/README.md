# Import/Export Utilities

This directory contains utilities for importing and exporting publication data (or other pages with the appropriate alterations) in WordPress, specifically designed to work with Advanced Custom Fields (ACF) repeater fields.

## Files Overview

### 1. `export-acf-repeater.php`
A script for exporting publication data from an ACF repeater field to a CSV file.

#### Features:
- Exports data from a specific page (ID: 14779)
- Handles ACF repeater field named 'publication'
- Exports the following fields:
  - Title
  - Authors
  - Year Date (Yes/No format)
  - Publication Date
  - URL
  - Subtitle
  - Image URL
- Includes debugging logs for image data
- Outputs CSV directly to browser for download

#### Usage:
1. Access the script through your browser
2. CSV file will automatically download
3. Check error logs for any image-related issues

### 2. `import-publications.php`
A script for importing publication data from a CSV file into WordPress posts.

#### Features:
- Processes CSV file (`import-publications-sci-com.csv`)
- Creates new 'publication' post type entries
- Handles ACF field updates
- Manages featured image attachments
- Includes comprehensive error logging
- Temporarily disables theme hooks during import

#### CSV Format Requirements:
The import script expects a CSV file with the following columns:
1. Title
2. Authors
3. Year Date (Yes/No)
4. Publication Date
5. URL
6. Subtitle
7. Image URL

#### Usage:
1. Place your CSV file in the theme directory as `import-publications-sci-com.csv`
2. Run the script through your browser or command line
3. Check error logs for import status and any issues

## Error Handling

Both scripts include comprehensive error logging:
- Failed post creation
- ACF field update issues
- Image attachment problems
- CSV processing errors

## Dependencies

- WordPress environment
- Advanced Custom Fields (ACF) plugin
- Proper file permissions for CSV operations
- Access to WordPress media library

## Security Considerations

- Scripts include proper sanitization of input data
- URLs are properly escaped
- File operations are checked for existence
- WordPress nonces and capabilities are respected

## Notes

- The export script is configured for a specific page ID (14779) - modify this as needed
- Image handling requires existing images to be present in the WordPress media library
- Theme hooks are temporarily disabled during import to prevent conflicts
- Both scripts require proper WordPress environment setup

## Troubleshooting

If you encounter issues:
1. Check WordPress error logs
2. Verify CSV file format and permissions
3. Ensure all required ACF fields exist
4. Confirm image URLs are accessible
5. Verify WordPress user permissions
