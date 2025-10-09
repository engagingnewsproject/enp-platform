<?php

/**
 * The template for displaying Blog Archive pages.
 *
 * This template is used to display the main blog archive page (/blogs).
 * It includes functionality to handle blog category filtering and display.
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
 * Get the posts per page from ACF options
 * This is used to set the number of posts to display on the archive page
 */
$posts_per_page = $archive_settings['blogs_post_type']['blogs_archive_posts_per_page'];

/**
 * Get the archive filters from ACF options
 * These filters are used to determine which blog categories should be excluded
 * from the archive page
 */
$excluded_categories = $archive_settings['blogs_post_type']['blogs_archive_filter'] ?? [];
$title = $archive_settings['blogs_post_type']['blogs_archive_title'];
$sidebar = $archive_settings['blogs_post_type']['blogs_archive_sidebar'];

/**
 * Define the template hierarchy for this archive page
 */
$templates = ['templates/archive.twig', 'templates/index.twig'];

// If title is empty, get the default post type label
if (empty($title)) {
	$post_type_obj = get_post_type_object('blogs');
	$title = $post_type_obj->labels->name;
}

// Override title with category name if on a category page
if (is_tax('blogs-category')) {
	$term = get_queried_object();
	$title = $term->name;
}

/**
 * Get sidebar filters for blogs
 */
if ($sidebar) {
	$options = [
		'filters' => $globals->getBlogMenu(),
		'postType' => 'blogs'
	];
} else {
	$options = [];
}

/**
 * Handle blogs category filtering
 */
if (is_post_type_archive('blogs') || is_tax('blogs-category')) {
	$blogs_category = get_query_var('blogs-category');
	if ($blogs_category) {
		$args = [
			'post_type' => 'blogs',
			'tax_query' => [
				[
					'taxonomy' => 'blogs-category',
					'field' => 'slug',
					'terms' => $blogs_category
				]
			],
			'posts_per_page' => $posts_per_page, // Use ACF option
			'paged' => $paged
		];
		$wp_query = new WP_Query($args);
	} elseif (!empty($excluded_categories)) {
		$args = [
			'post_type' => 'blogs',
			'posts_per_page' => $posts_per_page, // Use ACF option
			'paged' => $paged,
			'tax_query' => [
				[
					'taxonomy' => 'blogs-category',
					'field'    => 'term_id',
					'terms'    => array_map(
						fn($category) => $category->term_id,
						$excluded_categories
					),
					'operator' => 'NOT IN'
				]
			]
		];

		$wp_query = new WP_Query($args);
	} else {
		// Add default query for base /blogs URL
		$args = [
			'post_type' => 'blogs',
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
if (is_tax('blogs-category')) {
	$current_term = get_query_var('blogs-category');
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
	'pagination' => [
		'posts_per_page' => get_option('posts_per_page'),
		'paged' => $paged
	],
	'sidebar' => $sidebar
]);

/**
 * Render the template with our context
 */
Timber::render($templates, $context);
