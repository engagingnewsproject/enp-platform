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
global $wp_query;
global $paged;

if (!isset($paged) || !$paged) {
    $paged = 1;
}

/**
 * Initialize the context and get global variables
 */
$context = Timber::context();
$globals = new Engage\Managers\Globals();
$archive_settings = get_field('archive_settings', 'options');

/**
 * Define the template hierarchy for this archive page
 */
$templates = ['templates/archive.twig', 'templates/index.twig'];

/**
 * Get the posts per page from ACF options
 * This is used to set the number of posts to display on the archive page
 */
$posts_per_page = $archive_settings['announcement_post_type']['announcement_archive_posts_per_page'];

/**
 * Get the archive filters from ACF options
 * These filters are used to determine which announcement categories should be excluded
 * from the archive page
 */
$excluded_categories = $archive_settings['announcement_post_type']['announcement_archive_filter'] ?? [];
$title = $archive_settings['announcement_post_type']['announcement_archive_title'];
$sidebar = $archive_settings['announcement_post_type']['announcement_archive_sidebar'];

// If title is empty, get the default post type label
if (empty($title)) {
    $post_type_obj = get_post_type_object('announcement');
    $title = $post_type_obj->labels->name;
}

// Override title with category name if on a category page
if (is_tax('announcement-category')) {
    $term = get_queried_object();
    $title = $term->name;
}

/**
 * Get sidebar filters for announcement
 */
if ($sidebar) {
$options = [
    'filters' => $globals->getAnnouncementMenu(),
    'postType' => 'announcement'
];
} else {
	$options = [];
}

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
			'posts_per_page' => $posts_per_page, // Use WordPress Reading Settings
			'paged' => $paged
        ];
        $wp_query = new WP_Query($args);
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
            'post_type' => get_post_type(),
			'posts_per_page' => $posts_per_page, // Use ACF option
			'paged' => $paged,
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
        $wp_query = new WP_Query($args);
    } else {
		// Add this default query for the main archive page
		$args = [
			'post_type' => 'announcement',
			'posts_per_page' => $posts_per_page,
			'paged' => $paged
		];
		$wp_query = new WP_Query($args);
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
    'archive_filters' => $excluded_categories,
	'posts_per_page' => $posts_per_page, // Use ACF option
	'paged' => $paged,
	'sidebar' => $sidebar
]);

/**
 * Render the template with our context
 */
Timber::render($templates, $context); 