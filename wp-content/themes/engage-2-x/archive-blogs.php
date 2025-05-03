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
use Engage\Models\BlogsFilterMenu;
global $wp_query;

/**
 * Initialize the context and get global variables
 */
$context = Timber::context();
$globals = new Engage\Managers\Globals();
$options = [];

/**
 * Define the template hierarchy for this archive page
 */
$templates = ['templates/archive-blogs.twig', 'templates/archive.twig', 'templates/index.twig'];

/**
 * Get sidebar filters for blogs
 */
$options = ['filters' => $globals->getBlogMenu()];

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
            'posts_per_page' => -1
        ];
        $wp_query = new \WP_Query($args);
    }
}

/**
 * Get current term for filter highlighting
 */
$current_term = '';
if (is_tax('blogs-category')) {
    $current_term = get_query_var('blogs-category');
}

$context['current_term'] = $current_term;

/**
 * Create a TileArchive object for handling the archive display
 */
$archive = new TileArchive($options, $wp_query);
$context['archive'] = $archive;

/**
 * Get the posts for the archive
 */
$context['posts'] = Timber::get_posts();

/**
 * Render the template with our context
 */
Timber::render($templates, $context);
