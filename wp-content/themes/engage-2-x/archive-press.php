<?php
/**
 * The template for displaying Press Archive pages.
 *
 * This template is used to display the main press archive page (/press).
 * It includes functionality to filter out specific press categories based on
 * ACF options settings.
 *
 * @package Engage
 */

use Timber\Timber;
use Engage\Models\TileArchive;
global $wp_query;

/**
 * Initialize the context and get global variables
 * 
 * The context array will be passed to the Twig template for rendering.
 * We also initialize the Globals manager which handles various site-wide settings.
 */
$context = Timber::context();
$globals = new Engage\Managers\Globals();
$options = [];

/**
 * Define the template hierarchy for this archive page
 * WordPress will look for these templates in order, using the first one it finds
 */
$templates = ['templates/archive-press.twig', 'templates/archive.twig', 'templates/index.twig'];

/**
 * Get the archive filters from ACF options
 * These filters are used to determine which press categories should be excluded
 * from the archive page
 */
$archive_settings = get_field('archive_settings', 'options');
$excluded_categories = $archive_settings['press_post_type']['press_archive_filter'] ?? [];
$title = $archive_settings['press_post_type']['press_archive_title'];

// If title is empty, get the default post type label
if (empty($title)) {
    $post_type_obj = get_post_type_object('press');
    $title = $post_type_obj->labels->name;
}

/**
 * Update the context with the title and archive filters
 * This ensures these variables are available in the Twig template
 */
$context = Timber::context([
    'title' => $title,
    'archive_filters' => $excluded_categories,
]);

/**
 * Handle category filtering
 * 
 * If there are categories set to be excluded in the ACF options,
 * we'll modify the query to exclude those categories
 */
if (!empty($excluded_categories)) {
    // Convert category objects to an array of term IDs
    $excluded_category_ids = array_map(
        fn($category) => $category->term_id, 
        $excluded_categories
    );

    /**
     * Build the query arguments
     * We're using tax_query to exclude the specified categories
     */
    $args = [
        'post_type' => 'press',
        'posts_per_page' => -1, // Show all posts
        'tax_query' => [
            [
                'taxonomy' => 'press-categories',
                'field'    => 'term_id',
                'terms'    => $excluded_category_ids,
                'operator' => 'NOT IN'
            ]
        ]
    ];

    // Get posts that don't belong to excluded categories
    $context['posts'] = Timber::get_posts($args);
} else {
    // If no categories are excluded, get all press posts
    $args = [
        'post_type' => 'press',
        'posts_per_page' => -1 // Show all posts
    ];
    $context['posts'] = Timber::get_posts($args);
}

/**
 * Create a TileArchive object for handling the archive display
 * This object provides additional functionality for displaying posts in a grid/tile format
 */
$archive = new TileArchive($options, $wp_query);

/**
 * Set the intro property with the custom title from ACF options
 * This overrides the default title that would be set by the parent Archive class
 * The title will be used in the tile-intro.twig template to display the archive header
 */
$archive->intro = [
    'title' => $title,
    'excerpt' => ''
];
$context['archive'] = $archive;

/**
 * Render the template with our context
 * Timber will use the first template it finds in the $templates array
 */
Timber::render($templates, $context);
