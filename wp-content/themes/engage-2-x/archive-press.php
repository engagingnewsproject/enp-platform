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
 * Set the page title using WordPress's post type archive title function
 * This will display "Press" or whatever the post type label is set to
 */
$title = post_type_archive_title('', false);

/**
 * Get the archive filters from ACF options
 * These filters are used to determine which press categories should be excluded
 * from the archive page
 */
$archive_filters = get_field('archive_settings', 'options');

/**
 * Update the context with the title and archive filters
 * This ensures these variables are available in the Twig template
 */
$context = Timber::context([
    'title' => $title,
    'archive_filters' => $archive_filters,
]);

/**
 * Handle category filtering
 * 
 * If there are categories set to be excluded in the ACF options,
 * we'll modify the query to exclude those categories
 */
$excluded_categories = $context['archive_filters']['press_archive_filter'] ?? [];
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
$context['archive'] = $archive;

/**
 * Render the template with our context
 * Timber will use the first template it finds in the $templates array
 */
Timber::render($templates, $context);
