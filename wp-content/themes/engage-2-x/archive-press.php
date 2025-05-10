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
global $paged;

if (!isset($paged) || !$paged) {
    $paged = 1;
}

/**
 * Initialize the context and get global variables
 * 
 * The context array will be passed to the Twig template for rendering.
 * We also initialize the Globals manager which handles various site-wide settings.
 */
$context = Timber::context();
$globals = new Engage\Managers\Globals();
$options = [];
$archive_settings = get_field('archive_settings', 'options');

/**
 * Get the posts per page from ACF options
 * This is used to set the number of posts to display on the archive page
 */
$posts_per_page = $archive_settings['press_post_type']['press_archive_posts_per_page'];

/**
 * Get the archive filters from ACF options
 * These filters are used to determine which press categories should be excluded
 * from the archive page
 */
$excluded_categories = $archive_settings['press_post_type']['press_archive_filter'] ?? [];
$title = $archive_settings['press_post_type']['press_archive_title'];

/**
 * Define the template hierarchy for this archive page
 * WordPress will look for these templates in order, using the first one it finds
 */
$templates = ['templates/archive-press.twig', 'templates/archive.twig', 'templates/index.twig'];


// If title is empty, get the default post type label
if (empty($title)) {
    $post_type_obj = get_post_type_object('press');
    $title = $post_type_obj->labels->name;
}

// Set up the query with pagination
$args = array(
	'post_type' => get_post_type(),
	'posts_per_page' => $posts_per_page, // Use ACF option
	'paged' => $paged
);

// Add tax query to exclude the filtered categories
if (!empty($excluded_categories)) {
    // Convert term objects to IDs
    $excluded_category_ids = array_map(
        function($cat) { return is_object($cat) ? $cat->term_id : $cat; },
        $excluded_categories
    );
    
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'press-categories',
            'field' => 'term_id',  // Changed from 'slug' to 'term_id'
            'terms' => $excluded_category_ids,
            'operator' => 'NOT IN'
        )
    );
}

// Create a new query with pagination
$wp_query = new WP_Query($args);

/**
 * Update the context with the title and archive filters
 * This ensures these variables are available in the Twig template
 */
$context = Timber::context([
    'title' => $title,
    'archive_filters' => $excluded_categories,
	'posts_per_page' => $posts_per_page, // Use ACF option
	'paged' => $paged,
]);

// Use the main query, which is now filtered and ordered by pre_get_posts
$context['posts'] = Timber::get_posts($wp_query);

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
