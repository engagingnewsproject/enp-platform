<?php
/**
 * Template for displaying Research Archive pages
 * 
 * Handles three types of research archives:
 * 1. Main research archive (excludes media-ethics and uncategorized)
 * 2. Media Ethics category page (displays subcategories as tiles)
 * 3. Media Ethics subcategory pages (displays posts within a subcategory)
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
$templates = ['templates/archive-research.twig', 'templates/archive.twig', 'templates/index.twig'];

// Get archive settings from ACF options
$archive_settings = get_field('archive_settings', 'options');
$posts_per_page = $archive_settings['research_post_type']['research_archive_posts_per_page'];

// Get sidebar filters for research categories
$options = ['filters' => $globals->getResearchMenu()];

// Handle research category pages
if (is_tax('research-categories')) {
    $research_categories = get_query_var('research-categories');
    
    // Main media ethics category page - displays subcategories as tiles
    if (is_string($research_categories) && $research_categories === 'media-ethics') {
        $context['archive'] = handle_media_ethics_category($options, $research_categories);
    } 
    // Media ethics subcategory pages - displays posts within a subcategory
    else {
        $media_ethics_subcategory_archive = handle_media_ethics_subcategory($options, $research_categories, $posts_per_page);
        if ($media_ethics_subcategory_archive) {
            $context['archive'] = $media_ethics_subcategory_archive;
        } else {
            // Default research category archive - excludes media-ethics and uncategorized
            $args = [
                'post_type' => 'research',
                'posts_per_page' => $posts_per_page,
                'paged' => $paged,
                'tax_query' => [
                    [
                        'taxonomy' => 'research-categories',
                        'field' => 'slug',
                        'terms' => get_query_var('term')
                    ],
                    [
                        'taxonomy' => 'research-categories',
                        'field' => 'slug',
                        'terms' => ['media-ethics', 'uncategorized'],
                        'operator' => 'NOT IN'
                    ]
                ]
            ];
            
            $wp_query = new WP_Query($args);
            $archive = new TileArchive($options, $wp_query);
            $context['archive'] = $archive;
        }
    }
} else {
    // Main research archive page - excludes media-ethics and uncategorized
    $args = [
        'post_type' => 'research',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'tax_query' => [
            [
                'taxonomy' => 'research-categories',
                'field' => 'slug',
                'terms' => ['media-ethics', 'uncategorized'],
                'operator' => 'NOT IN'
            ]
        ]
    ];
    
    $wp_query = new WP_Query($args);
    $archive = new TileArchive($options, $wp_query);
    $context['archive'] = $archive;
}

/**
 * Helper Functions
 */

/**
 * Excludes the 'uncategorized' term from taxonomy queries
 */
function exclude_uncategorized_terms($args) {
    $uncategorized = get_term_by('slug', 'uncategorized', 'research-categories');
    if ($uncategorized) {
        $args['exclude'] = $uncategorized->term_id;
    }
    return $args;
}

/**
 * Handles the main Media Ethics category page
 * Creates a grid of subcategory tiles with featured images
 */
function handle_media_ethics_category($options, $research_categories)
{
    if (!((is_array($research_categories) && in_array('media-ethics', $research_categories)) ||
        get_query_var('research-categories') === 'media-ethics')) {
        return null;
    }

    // Get research categories
    $args = [
        'taxonomy' => 'research-categories',
        'hide_empty' => true
    ];

    // Apply uncategorized exclusion
    $args = exclude_uncategorized_terms($args);

    $researchCategories = get_terms($args);
    $researchTiles = [];

    // Create tiles for each category with featured image
    foreach ($researchCategories as $category) {
        $thumbID = get_field('category_featured_image', "research-categories_{$category->term_id}");

        if ($thumbID) {
            $image = Timber::get_image($thumbID);            
            $researchTiles[] = [
                'ID' => $category->term_id,
                'title' => $category->name,
                'excerpt' => $category->description,
                'thumbnail' => $image,
                'link' => home_url("/research/category/media-ethics/{$category->slug}/"),
                'count' => $category->count
            ];
        }
    }

    // Create a WP_Query with no posts since we're using custom tiles
    $query = new WP_Query([
        'post_type' => 'research',
        'posts_per_page' => 0
    ]);

    // Create a TileArchive with the options and tiles
    $archive = new TileArchive($options, $query);
    $archive->posts = $researchTiles;
    return $archive;
}

/**
 * Handles Media Ethics subcategory pages
 * Displays posts that belong to both 'media-ethics' and the specific subcategory
 */
function handle_media_ethics_subcategory($options, $research_categories, $posts_per_page)
{
    $is_media_ethics_page = false;
    $subcategories = [];
    
    // Check if we're on a media-ethics subcategory page
    if (is_string($research_categories)) {
        $categories = explode(',', $research_categories);
        if (in_array('media-ethics', $categories)) {
            $is_media_ethics_page = true;
            $subcategories = array_filter($categories, function ($cat) {
                return $cat !== 'media-ethics';
            });
        }
    } else if (is_array($research_categories) && in_array('media-ethics', $research_categories)) {
        $is_media_ethics_page = true;
        $subcategories = array_filter($research_categories, function ($cat) {
            return $cat !== 'media-ethics';
        });
    }

    if (!$is_media_ethics_page || empty($subcategories)) {
        return null;
    }

    // Get the current subcategory term
    $current_subcategory = get_term_by('slug', reset($subcategories), 'research-categories');
    if (!$current_subcategory) {
        return null;
    }

    // Query posts with both categories and pagination
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
        'posts_per_page' => $posts_per_page,
        'paged' => $GLOBALS['paged']
    ];

    $query = new WP_Query($args);
    $archive = new TileArchive($options, $query);
    
    // Set up the intro object with title and subtitle
    $archive->intro = [
        'title' => 'Media Ethics',
        'subtitle' => $current_subcategory->name,
        'excerpt' => $current_subcategory->description
    ];
    
    return $archive;
}

$context['posts'] = Timber::get_posts();
Timber::render($templates, $context);
