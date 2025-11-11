<?php
/**
 * Advanced Custom Fields (ACF) related hooks and filters
 * 
 * This file contains all hooks and filters related to ACF functionality,
 * including field settings, JSON sync, and admin interface modifications.
 */

// Only proceed if ACF is active
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (is_plugin_active('advanced-custom-fields-pro/acf.php')) {
    // Set custom load and save paths for ACF JSON
    add_filter('acf/settings/save_json', function () {
        return get_stylesheet_directory() . '/acf-json';
    });

    add_filter('acf/settings/load_json', function ($paths) {
        // Clear the default ACF JSON folder
        unset($paths[0]);
        // Add your custom path
        $paths[] = get_stylesheet_directory() . '/acf-json';
        return $paths;
    });

    add_filter('acf/settings/show_admin', '__return_true');
    add_filter('acf/settings/json', '__return_true');

    // Modify the research sidebar filter field settings
    add_filter('acf/load_field/key=field_67f6750079f59', function($field) {
        // Disable automatic term management
        $field['save_terms'] = 0;
        $field['load_terms'] = 0;
        return $field;
    });

    // Clear ACF and menu cache when options are saved
    add_action('acf/save_post', function($post_id) {
        if ($post_id === 'options') {
            // Clear ACF cache
            wp_cache_delete('options_archive_settings', 'acf');
            wp_cache_delete('options_archive_settings_research_sidebar_filter', 'acf');
            
            // Clear the research filter menu transient
            delete_transient('research-filter-menu');
            delete_option('research_filter_menu_keys');

            if (class_exists('\Engage\Managers\Globals')) {
                (new \Engage\Managers\Globals())->clearResearchMenu(0, 0);
            }
            
            // Also clear WordPress object cache
            wp_cache_flush();
        }
    }, 20); // Run after save

    // Update with correct field name and ensure clean values
    add_filter('acf/update_value/name=archive_settings_research_sidebar_filter', function($value, $post_id, $field) {
        // Ensure we have an array
        if (!is_array($value)) {
            $value = $value ? array($value) : array();
        }
        
        // Clean the array: remove duplicates, empty values, and non-numeric values
        $value = array_filter($value, function($v) {
            return !empty($v) && is_numeric($v);
        });
        $value = array_values(array_unique($value));
        
        return $value;
    }, 10, 3);

    // This filter allows you to customize the title displayed 
    // for each layout in the Flexible Content field based on field values
    add_filter('acf/fields/flexible_content/layout_title', function ($title, $field, $layout, $i) {
        if ($layout['name'] === 'wysiwyg' || $layout['name'] === 'highlights' || 
            $layout['name'] === 'research_initiatives' || $layout['name'] === 'parallax') {
            // Access the "header" subfield inside the "header_group"
            $header_group = get_sub_field('header_group');
            if ($header_group && isset($header_group['header']) && $header_group['header'] != '') {
                $header_title = $header_group['header'];
                $title .= ' - ' . esc_html($header_title);
            }
        }
        return $title;
    }, 10, 4);

    // Disable ACF field group editing interface in production
    add_filter('acf/settings/show_admin', function ($show_admin) {
        if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'production') {
            return false;
        }
        return $show_admin;
    });
} 