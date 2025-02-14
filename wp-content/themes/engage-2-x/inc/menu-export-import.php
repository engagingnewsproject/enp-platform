<?php
/**
 * WordPress Menu Export & Import
 * 
 * This script provides an easy way to export a WordPress menu as a JSON file
 * and later import it into another WordPress installation.
 *
 * 📌 Features:
 * - Adds an admin submenu under "Appearance" for exporting/importing menus.
 * - Exports a menu in JSON format, preserving structure & links.
 * - Imports a menu, recreating it with the same items.
 *
 * @package YourTheme
 */

// Hook to add a new submenu in the WP Admin under "Appearance"
add_action('admin_menu', function () {
    add_submenu_page(
        'themes.php', // Parent menu (Appearance)
        'Export/Import Menus', // Page title
        'Export/Import Menus', // Menu title
        'manage_options', // Capability (only admins can see it)
        'export-import-menus', // Menu slug
        'export_import_menu_callback' // Callback function to display content
    );
});

/**
 * Callback function that renders the Export/Import page in WP Admin.
 */
function export_import_menu_callback() {
    // Handle Export Button Click
    if (isset($_POST['export_menu'])) {
        echo '<div class="updated"><p><strong>' . export_wp_menu('Main Menu') . '</strong></p></div>';
    }

    // Handle Import Button Click
    if (isset($_POST['import_menu'])) {
        echo '<div class="updated"><p><strong>' . import_wp_menu('menu-export.json', 'Imported Menu') . '</strong></p></div>';
    }

    // Display Admin UI for Export/Import Actions
    echo '<div class="wrap">
            <h1>Export & Import Menus</h1>
            <form method="POST">
                <p>
                    <button type="submit" name="export_menu" class="button button-primary">Export Main Menu</button>
                    <button type="submit" name="import_menu" class="button button-secondary">Import Menu</button>
                </p>
            </form>
          </div>';
}

/**
 * Export a WordPress menu as a JSON file.
 *
 * @param string $menu_name The name of the menu to export.
 * @return string Success or failure message.
 */
function export_wp_menu($menu_name) {
    // Get the menu object by name
    $menu = wp_get_nav_menu_object($menu_name);
    if (!$menu) return "Menu not found.";

    // Retrieve menu items
    $menu_items = wp_get_nav_menu_items($menu->term_id);
    $export_data = [];

    // Loop through each menu item and store its properties
    foreach ($menu_items as $item) {
        $export_data[] = [
            'title'       => $item->title, // Menu item title
            'url'         => $item->url, // Menu item URL
            'menu_order'  => $item->menu_order, // Order in the menu
            'type'        => $item->type, // Type (custom, post, category, etc.)
            'object_id'   => $item->object_id, // Associated object ID
            'object'      => $item->object, // Type of WP object (post, category, etc.)
            'parent'      => $item->menu_item_parent, // Parent menu item ID
            'target'      => $item->target, // Link target (_blank, _self, etc.)
            'classes'     => implode(" ", $item->classes), // CSS classes
            'xfn'         => $item->xfn, // XFN (rel) attributes
            'description' => $item->description // Menu item description
        ];
    }

    // Convert data to JSON format
    $json = json_encode($export_data, JSON_PRETTY_PRINT);

    // Save JSON to the theme directory
    file_put_contents(get_template_directory() . '/menu-export.json', $json);

    return "Menu exported successfully!";
}

/**
 * Import a WordPress menu from a JSON file.
 *
 * @param string $json_file The JSON file containing menu data.
 * @param string $new_menu_name The name for the imported menu.
 * @return string Success or failure message.
 */
function import_wp_menu($json_file, $new_menu_name) {
    // Read JSON file and decode it
    $menu_data = json_decode(file_get_contents(get_template_directory() . '/' . $json_file), true);
    if (!$menu_data) return "Invalid JSON file!";

    // Check if the menu already exists
    $menu_exists = wp_get_nav_menu_object($new_menu_name);
    if (!$menu_exists) {
        // Create a new menu if it doesn't exist
        $menu_id = wp_create_nav_menu($new_menu_name);
    } else {
        // Use the existing menu
        $menu_id = $menu_exists->term_id;
    }

    // Loop through menu items and add them to the menu
    foreach ($menu_data as $item) {
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'     => $item['title'], // Title
            'menu-item-url'       => $item['url'], // URL
            'menu-item-status'    => 'publish', // Make it visible
            'menu-item-parent-id' => $item['parent'], // Parent ID (for nesting)
            'menu-item-target'    => $item['target'], // Target (_blank, _self)
            'menu-item-classes'   => $item['classes'], // CSS Classes
            'menu-item-xfn'       => $item['xfn'], // XFN attributes
            'menu-item-description' => $item['description'] // Description
        ]);
    }

    return "Menu imported successfully!";
}
