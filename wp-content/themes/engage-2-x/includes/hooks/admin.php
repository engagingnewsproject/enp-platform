<?php
/**
 * Admin-related hooks and filters
 * 
 * This file contains all hooks and filters related to the WordPress admin area,
 * including editor customizations and admin interface modifications.
 */

/**
 * Add Custom Styles to the Classic Editor Dropdown
 */
function custom_tinymce_style_formats($init_array) {
    $style_formats = [
        [
            'title' => 'Paragraph Small',
            'block' => 'p',
            'classes' => 'p-small',
            'wrapper' => false,
        ],
        [
            'title' => 'Paragraph 1',
            'block' => 'p',
            'classes' => 'p-1',
            'wrapper' => false,
        ],
        [
            'title' => 'Paragraph 2',
            'block' => 'p',
            'classes' => 'p-2',
            'wrapper' => false,
        ],
        [
            'title' => 'Paragraph 3',
            'block' => 'p',
            'classes' => 'p-3',
            'wrapper' => false,
        ],
    ];

    $init_array['style_formats'] = json_encode($style_formats);
    return $init_array;
}
add_filter('tiny_mce_before_init', 'custom_tinymce_style_formats');

/**
 * Enable the Styles Dropdown in the Classic Editor
 */
function add_custom_editor_buttons($buttons) {
    array_unshift($buttons, 'styleselect');
    return $buttons;
}
add_filter('mce_buttons', 'add_custom_editor_buttons'); 