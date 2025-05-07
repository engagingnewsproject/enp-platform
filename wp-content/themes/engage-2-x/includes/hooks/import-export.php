<?php
/**
 * Import/Export functionality for WordPress admin
 * 
 * This file contains functions for adding import/export menu items
 * and handling the import/export processes.
 */

/**
 * Add Export CSV menu page to WordPress admin
 */
function add_export_csv_menu_page() {
    add_menu_page(
        'Export Repeater CSV', 
        'Export Repeater CSV', 
        'manage_options', 
        'export-repeater-csv', 
        'export_repeater_csv_page'
    );
}

/**
 * Render the Export CSV admin page
 */
function export_repeater_csv_page() {
    if (isset($_POST['run_export'])) {
        require_once(get_template_directory() . '/tests/export-import/export-repeater-csv.php');
    }
    ?>
    <div class="wrap">
        <h1>Export Repeater to CSV</h1>
        <form method="post">
            <p>Click the button below to export the repeater field content to CSV.</p>
            <input type="submit" name="run_export" class="button button-primary" value="Export to CSV">
        </form>
    </div>
    <?php
}

/**
 * Add Import Publications menu page to WordPress admin
 */
function add_import_publications_menu_page() {
    add_menu_page(
        'Import Publications', 
        'Import Publications', 
        'manage_options', 
        'import-publications', 
        'import_publications_page'
    );
}

/**
 * Render the Import Publications admin page
 */
function import_publications_page() {
    if (isset($_POST['run_import'])) {
        require_once(get_template_directory() . '/tests/export-import/import-publications.php');
    }
    ?>
    <div class="wrap">
        <h1>Import Publications from CSV</h1>
        <form method="post">
            <p>Click the button below to import publications from the CSV file.</p>
            <input type="submit" name="run_import" class="button button-primary" value="Import Publications">
        </form>
    </div>
    <?php
}

// Register the menu pages
add_action('admin_menu', 'add_export_csv_menu_page');
add_action('admin_menu', 'add_import_publications_menu_page'); 