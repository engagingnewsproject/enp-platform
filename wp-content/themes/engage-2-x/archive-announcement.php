<?php
/**
 * The template for displaying Announcement Archive pages.
 *
 * This template is used to display the main announcement archive page (/announcement).
 * It includes functionality to handle announcement category filtering and display.
 *
 * @package Engage
 */

use Timber\Timber;
use Engage\Models\TileArchive;
use Engage\Models\AnnouncementFilterMenu;
global $wp_query;

/**
 * Initialize the context and get global variables
 */
$context = Timber::context();
$globals = new Engage\Managers\Globals();

/**
 * Define the template hierarchy for this archive page
 */
$templates = ['templates/archive.twig', 'templates/index.twig'];

/**
 * Get the archive filters from ACF options
 * These filters are used to determine which announcement categories should be excluded
 * from the archive page
 */
$archive_settings = get_field('archive_settings', 'options');
$excluded_categories = $archive_settings['announcement_post_type']['announcement_archive_filter'] ?? [];
$title = $archive_settings['announcement_post_type']['announcement_archive_title'];

// If title is empty, get the default post type label
if (empty($title)) {
    $post_type_obj = get_post_type_object('announcement');
    $title = $post_type_obj->labels->name;
}

/**
 * Get sidebar filters for announcement
 */
$options = [
    'filters' => $globals->getAnnouncementMenu(),
    'postType' => 'announcement'
];

/**
 * Handle announcement category filtering
 */
if (is_post_type_archive('announcement') || is_tax('announcement-category')) {
    $announcement_category = get_query_var('announcement-category');
    if ($announcement_category) {
        $args = [
            'post_type' => 'announcement',
            'tax_query' => [
                [
                    'taxonomy' => 'announcement-category',
                    'field' => 'slug',
                    'terms' => $announcement_category
                ]
            ],
            'posts_per_page' => -1
        ];
        $wp_query = new \WP_Query($args);
    } elseif (!empty($excluded_categories)) {
        // Get all announcement categories
        $all_categories = get_terms([
            'taxonomy' => 'announcement-category',
            'hide_empty' => true,
            'exclude' => array_map(
                fn($category) => $category->term_id, 
                $excluded_categories
            )
        ]);

        // Build the query arguments to include only the non-excluded categories
        $args = [
            'post_type' => 'announcement',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'announcement-category',
                    'field'    => 'term_id',
                    'terms'    => array_map(
                        fn($category) => $category->term_id,
                        $all_categories
                    ),
                    'operator' => 'IN'
                ]
            ]
        ];
        $wp_query = new \WP_Query($args);
    }
}

/**
 * Get current term for filter highlighting
 */
$current_term = '';
if (is_tax('announcement-category')) {
    $current_term = get_query_var('announcement-category');
}

/**
 * Create a TileArchive object for handling the archive display
 */
$archive = new TileArchive($options, $wp_query);

/**
 * Set the intro property with the custom title from ACF options
 */
$archive->intro = [
    'title' => $title,
    'excerpt' => ''
];

/**
 * Update the context with all necessary data
 */
$context = array_merge($context, [
    'archive' => $archive,
    'current_term' => $current_term,
    'archive_filters' => $excluded_categories
]);

/**
 * Render the template with our context
 */
Timber::render($templates, $context); 