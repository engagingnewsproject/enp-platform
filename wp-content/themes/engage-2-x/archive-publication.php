<?php
/**
 * Template for displaying Publication Archive pages
 * 
 * Handles both the main publication archive and category archives.
 * Publications are sorted by publication date (meta field) and support category filtering.
 */

use Timber\Timber;
use Engage\Models\TileArchive;

// Get global variables
global $wp_query;
global $paged;

if (!isset($paged) || !$paged) {
    $paged = 1;
}

// Initialize context and templates
$context = Timber::context();
$globals = new Engage\Managers\Globals();
$options = [];
$templates = ['templates/archive-publication.twig', 'templates/archive.twig', 'templates/index.twig'];

// Get archive settings from ACF options
$archive_settings = get_field('archive_settings', 'options');
$publication_posts_per_page = $archive_settings['publication_post_type']['publication_archive_posts_per_page'];
$publication_excluded_categories = $archive_settings['publication_post_type']['publication_archive_filter'] ?? [];

// Get the archive title from ACF options or fall back to post type label
$title = !empty($archive_settings['publication_post_type']['publication_archive_title']) 
    ? $archive_settings['publication_post_type']['publication_archive_title']
    : post_type_archive_title('', false);
$context['title'] = $title;

// Handle publication category pages
if (is_tax('publication-categories')) {
    // Set up query for category archive
    $args = [
        'post_type' => 'publication',
        'posts_per_page' => $publication_posts_per_page,
        'paged' => $paged,
        'meta_key' => 'publication_date',
        'orderby' => [
            'meta_value_num' => 'DESC',
            'date' => 'DESC'
        ],
        'tax_query' => [
            [
                'taxonomy' => 'publication-categories',
                'field' => 'slug',
                'terms' => get_query_var('term')
            ]
        ]
    ];

    // Add category exclusion if we have categories to exclude
    if (!empty($publication_excluded_categories)) {
        $excluded_category_ids = array_map(
            function($cat) { return is_object($cat) ? $cat->term_id : $cat; },
            $publication_excluded_categories
        );
        
        $args['tax_query'][] = [
            'taxonomy' => 'publication-categories',
            'field' => 'term_id',
            'terms' => $excluded_category_ids,
            'operator' => 'NOT IN'
        ];
    }

    $wp_query = new WP_Query($args);
    $archive = new TileArchive($options, $wp_query);
    $archive->intro = [
        'title' => $title,
        'subtitle' => get_queried_object()->name
    ];
    $context['archive'] = $archive;
} else {
    // Main publication archive page
    $args = [
        'post_type' => 'publication',
        'posts_per_page' => $publication_posts_per_page,
        'paged' => $paged,
        'meta_key' => 'publication_date',
        'orderby' => [
            'meta_value_num' => 'DESC',
            'date' => 'DESC'
        ]
    ];

    // Add category exclusion if we have categories to exclude
    if (!empty($publication_excluded_categories)) {
        $excluded_category_ids = array_map(
            function($cat) { return is_object($cat) ? $cat->term_id : $cat; },
            $publication_excluded_categories
        );
        
        $args['tax_query'] = [
            [
                'taxonomy' => 'publication-categories',
                'field' => 'term_id',
                'terms' => $excluded_category_ids,
                'operator' => 'NOT IN'
            ]
        ];
    }

    $wp_query = new WP_Query($args);
    $archive = new TileArchive($options, $wp_query);
    $archive->intro = [
        'title' => $title
    ];
    $context['archive'] = $archive;
}

$context['posts'] = Timber::get_posts();
Timber::render($templates, $context);
