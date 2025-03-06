<?php

/**
 * The template for displaying Archive pages.
 *
 * This template handles various types of archives including:
 * - Research categories and subcategories
 * - Team archives
 * - Announcement archives
 * - Blog archives
 * - Event archives
 * 
 * Special handling is implemented for:
 * - Media ethics subcategories
 * - Bridging divides research
 * - Vertical-based archives
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.2
 */

use Engage\Models\TileArchive;

/**
 * Initialize the context and get global variables
 */
$context = Timber::context();
global $wp_query;

$globals = new Engage\Managers\Globals();
$options = [];
$teamGroups = [];

/**
 * Set sidebar filter options based on archive type
 */
function get_sidebar_filters($globals)
{
	if (get_query_var('vertical_base')) {
		return ['filters' => $globals->getVerticalMenu(get_query_var('verticals'))];
	}

	// Check each post type and return appropriate filters
	if (is_post_type_archive(['research']) || is_tax('research-categories')) {
		return ['filters' => $globals->getResearchMenu()];
	}

	if (is_post_type_archive(['announcement']) || is_tax('announcement-category')) {
		return ['filters' => $globals->getAnnouncementMenu()];
	}

	if (is_post_type_archive(['blogs']) || is_tax('blogs-category')) {
		return ['filters' => $globals->getBlogMenu()];
	}

	if (is_post_type_archive(['board']) || is_tax('board_category')) {
		return ['filters' => $globals->getBoardMenu()];
	}

	if (is_post_type_archive(['tribe_events'])) {
		return ['filters' => $globals->getEventMenu()];
	}

	// Default empty options if no match
	return [];
}

/**
 * Handle special case for bridging-divides research archive
 */
function handle_bridging_divides_archive($options, $wp_query)
{
	if (!is_tax('category', 'bridging-divides') || !is_post_type_archive(['research'])) {
		return null;
	}

	// Remove Quick Reads from sidebar
	if (isset($options['filters']['terms']['blogs'])) {
		unset($options['filters']['terms']['blogs']);
	}

	$args = [
		'post_type' => ['research', 'blogs'],
		'tax_query' => [
			'relation' => 'AND',
			[
				'taxonomy' => 'research-categories',
				'field' => 'slug',
				'terms' => ['bridging-divides'],
			],
		],
		'posts_per_page' => -1,
	];

	return new TileArchive($options, new WP_Query($args));
}

/**
 * Handle media ethics subcategory pages
 */
function handle_media_ethics_subcategory($options, $research_categories)
{
	$is_media_ethics_page = false;
	$subcategories = [];

	// Check if we're on a media-ethics subcategory page
	if (is_array($research_categories) && in_array('media-ethics', $research_categories)) {
		$is_media_ethics_page = true;
		$subcategories = array_filter($research_categories, function ($cat) {
			return $cat !== 'media-ethics';
		});
	} else if (is_string($research_categories) && strpos($research_categories, 'media-ethics,') === 0) {
		$is_media_ethics_page = true;
		$categories = explode(',', $research_categories);
		$subcategories = array_filter($categories, function ($cat) {
			return $cat !== 'media-ethics';
		});
	}

	if (!$is_media_ethics_page) {
		return null;
	}

	// Debug output
	error_log('Media Ethics Subcategory Page');
	error_log('Research Categories: ' . print_r($research_categories, true));

	// Query posts with both categories
	$args = [
		'post_type' => 'research',
		'tax_query' => [
			'relation' => 'AND',
			[
				'taxonomy' => 'research-categories',
				'field' => 'slug',
				'terms' => 'media-ethics'
			],
			[
				'taxonomy' => 'research-categories',
				'field' => 'slug',
				'terms' => $subcategories
			]
		],
		'posts_per_page' => -1
	];

	$query = new WP_Query($args);
	error_log('Found Posts: ' . $query->post_count);
	error_log('Query SQL: ' . $query->request);

	return new TileArchive($options, $query);
}

/**
 * Handle media ethics category page
 */
function handle_media_ethics_category($context, $research_categories)
{
	if (!((is_array($research_categories) && in_array('media-ethics', $research_categories)) ||
		get_query_var('research-categories') === 'media-ethics')) {
		return;
	}

	// Get research categories
	$args = [
		'taxonomy' => 'research-categories',
		'hide_empty' => true
	];

	// Only exclude uncategorized if it exists
	$uncategorized = get_term_by('slug', 'uncategorized', 'research-categories');
	if ($uncategorized) {
		$args['exclude'] = $uncategorized->term_id;
	}

	$researchCategories = get_terms($args);
	$researchTiles = [];

	foreach ($researchCategories as $category) {
		$thumbID = get_field('category_featured_image', "research-categories_{$category->term_id}");

		if ($thumbID) {
			$researchTiles[] = [
				'ID' => $category->term_id,
				'title' => $category->name,
				'description' => $category->description,
				'image' => Timber::get_image($thumbID),
				'link' => home_url("/research/category/media-ethics/{$category->slug}/"),
				'count' => $category->count
			];
		}
	}

	$context['archive']['posts'] = $researchTiles;
}

// Get sidebar filters
$options = get_sidebar_filters($globals);

// Initialize archive
$archive = handle_bridging_divides_archive($options, $wp_query);
if (!$archive) {
	$archive = new TileArchive($options, $wp_query);
}
$context['archive'] = $archive;

// Handle announcement URLs
if (preg_match('/\/announcement\/([^\/]*\/)?([^\/]*(\/))?/', $_SERVER['REQUEST_URI'])) {
	$context['archive']['announcement'] = true;
}

// Handle media ethics pages
$research_categories = get_query_var('research-categories');
$media_ethics_archive = handle_media_ethics_subcategory($options, $research_categories);
if ($media_ethics_archive) {
	$context['archive'] = $media_ethics_archive;
	// Set the title for the subcategory page
	if (is_string($research_categories) && strpos($research_categories, ',') !== false) {
		$categories = explode(',', $research_categories);
		$subcategory = end($categories);
		$context['title'] = 'Media Ethics: ' . ucwords(str_replace('-', ' ', $subcategory));
	}
} else {
	handle_media_ethics_category($context, $research_categories);
}

// Render the template
Timber::render(['archive.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
