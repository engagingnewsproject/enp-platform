<?php
/**
 * Template for displaying Archive pages
 * 
 * Acts as a router for different post type archives:
 * 1. Routes research and publication archives to their dedicated handlers
 * 2. Handles other post type archives with default pagination
 * 3. Manages sidebar filters based on post type
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
$options = [];
$templates = [ 'templates/archive.twig', 'templates/index.twig' ];
$title = 'Archive';

/**
 * Get sidebar filter options based on post type
 * Returns appropriate menu filters for board and event archives
 */
function get_sidebar_filters($globals)
{
	// Check each post type and return appropriate filters
	if (is_post_type_archive(['board']) || is_tax('board_category')) {
		return ['filters' => $globals->getBoardMenu()];
	}

	if (is_post_type_archive(['tribe_events'])) {
		return ['filters' => $globals->getEventMenu()];
	}

	// Default empty options if no match
	return [];
}

// Get sidebar filters
$options = get_sidebar_filters($globals);

// Get current term for filter highlighting
$current_term = '';
if (is_tax('team_category')) {
	$current_term = get_query_var('team_category');
} elseif (is_tax('board_category')) {
	$current_term = get_query_var('board_category');
}

$context['current_term'] = $current_term;

/**
 * Route to specific post type handlers
 * Research and publication archives have dedicated handlers
 */
if (is_post_type_archive('research') || is_tax('research-categories')) {
    include get_template_directory() . '/archive-research.php';
    return;
}

if (is_post_type_archive('publication') || is_tax('publication-categories')) {
    include get_template_directory() . '/archive-publication.php';
    return;
}

/**
 * Handle other post types
 * Uses default WordPress pagination and post type templates
 */
if (is_post_type_archive()) {
    $post_type = get_post_type();
    $title = post_type_archive_title('', false);
    
    // Set up the query with pagination
    $args = [
        'post_type' => $post_type,
        'posts_per_page' => get_option('posts_per_page'), // Use WordPress default setting
        'paged' => $paged
    ];

    $wp_query = new WP_Query($args);
    $context = Timber::context([
        'title' => $title,
        'posts_per_page' => get_option('posts_per_page'), // Use WordPress default setting
        'paged' => $paged,
    ]);

    $archive = new TileArchive($options, $wp_query);
    $context['archive'] = $archive;
    array_unshift($templates, "templates/archive-{$post_type}.twig");
}

$context['posts'] = Timber::get_posts();
Timber::render($templates, $context);