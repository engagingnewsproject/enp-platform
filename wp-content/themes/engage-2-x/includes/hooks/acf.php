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

    /**
     * Formats the press article publication date meta (Ymd) for display.
     *
     * @param int $post_id Press post ID.
     */
    function engage_press_format_publication_date_meta(int $post_id): string
    {
        $raw_date = get_field('press_article_publication_date', $post_id);
        if (!is_string($raw_date) || strlen($raw_date) < 8) {
            return '';
        }

        $timestamp = strtotime(
            substr($raw_date, 0, 4) . '-' .
            substr($raw_date, 4, 2) . '-' .
            substr($raw_date, 6, 2) . ' 12:00:00'
        );

        return $timestamp ? wp_date('F j, Y', $timestamp) : '';
    }

    /**
     * Returns the date label shown next to each press title in the relationship picker.
     *
     * Uses "other" text when set; otherwise the publication date field; otherwise the
     * WordPress publish date so editors still see a date when other text is blank.
     *
     * @param int $post_id Press post ID.
     * @return string Display label, or empty string when none is set.
     */
    function engage_press_relationship_result_label(int $post_id): string
    {
        if (get_field('press_article_publication_date_other', $post_id)) {
            $other = get_field('press_article_publication_date_other_txt', $post_id);
            if (is_string($other) && $other !== '') {
                return $other;
            }
        }

        $publication_date = engage_press_format_publication_date_meta($post_id);
        if ($publication_date !== '') {
            return $publication_date;
        }

        $post = get_post($post_id);
        if ($post instanceof \WP_Post && $post->post_date) {
            $timestamp = strtotime($post->post_date);
            if ($timestamp) {
                return wp_date('F j, Y', $timestamp);
            }
        }

        return '';
    }

    // Each Press page gets its own order field; hide the others (shared field group).
    // Hook by field key — acf/prepare_field runs after $field['name'] is set to the key.
    foreach (\Engage\Models\PressPage::getManualOrderFieldKeyMap() as $field_key => $field_name) {
        add_filter("acf/prepare_field/key={$field_key}", function ($field) use ($field_name) {
            if (!is_array($field)) {
                return $field;
            }

            $page_id = \Engage\Models\PressPage::getEditorPageId();
            if ($page_id === 0 || get_post_type($page_id) !== 'page') {
                return false;
            }

            if (!\Engage\Models\PressPage::shouldShowManualOrderField($field_name, $page_id)) {
                return false;
            }

            $field['wrapper']['class'] = trim(($field['wrapper']['class'] ?? '') . ' acf-press-manual-order');

            return $field;
        });
    }

    // Date / "other" label beside titles in all manual-order relationship pickers.
    add_filter('acf/fields/relationship/result', function ($title, $post, $field, $post_id) {
        $field_name = is_array($field)
            ? \Engage\Models\PressPage::resolveManualOrderFieldName($field)
            : null;

        if ($field_name === null) {
            return $title;
        }

        if (!($post instanceof \WP_Post) || $post->post_type !== 'press') {
            return $title;
        }

        $label = engage_press_relationship_result_label((int) $post->ID);
        if ($label === '') {
            return $title;
        }

        return $title . '<span class="acf-relationship-press-meta"> — ' . esc_html($label) . '</span>';
    }, 10, 4);

    // Press manual-order relationship fields: date suffix + taller pick lists.
    add_action('acf/input/admin_enqueue_scripts', function () {
        if (!function_exists('get_current_screen')) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'post' || $screen->post_type !== 'page') {
            return;
        }

        wp_add_inline_style(
            'acf-input',
            '.acf-relationship .acf-relationship-press-meta{color:#646970;font-weight:400;}'
            // ACF sets .acf-relationship .list { height: 160px } — override the scrollable lists.
            . '.acf-press-manual-order .acf-relationship .list{height:20rem!important;}'
        );
    });
} 