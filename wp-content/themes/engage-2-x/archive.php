<?php
/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

use Timber\Timber;
use Engage\Models\TileArchive;
global $wp_query;

/**
 * Initialize the context and get global variables
 */
$context = Timber::context();
$globals = new Engage\Managers\Globals();
$options = [];
$templates = [ 'templates/archive.twig', 'templates/index.twig' ];
$title = 'Archive';

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
// Get sidebar filters
$options = get_sidebar_filters($globals);

/**
 * Handle media ethics category page
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
			$image = Timber::get_image($thumbID);			
			$researchTiles[] = [
				'ID' => $category->term_id,
				'title' => $category->name,
				'excerpt' => $category->description,
				'thumbnail' => $image,
				'link' => home_url("/research/category/media-ethics/{$category->slug}/"),
				'count' => $category->count
			];
		} else {
			error_log("No featured image found for category: {$category->name}");
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
 * Handle media ethics subcategory pages
 */
function handle_media_ethics_subcategory($options, $research_categories)
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
    $archive = new TileArchive($options, $query);
    
    // Set up the intro object with title and subtitle
    $archive->intro = [
        'vertical' => false,
        'title' => 'Media Ethics',
        'subtitle' => $current_subcategory->name,
        'excerpt' => $current_subcategory->description
    ];
    
    return $archive;
}

if ( is_day() ) {
	$title = 'Archive: ' . get_the_date( 'D M Y' );
} elseif ( is_month() ) {
	$title = 'Archive: ' . get_the_date( 'M Y' );
} elseif ( is_year() ) {
	$title = 'Archive: ' . get_the_date( 'Y' );
} elseif ( is_tag() ) {
	$title = single_tag_title( '', false );
} elseif ( is_category() ) {
	$title = single_cat_title( '', false );
} elseif (is_tax()) { // taxonomy
    $research_categories = get_query_var('research-categories');
    
    // First check if this is the main media ethics category page
    if (is_string($research_categories) && $research_categories === 'media-ethics') {
        $context['archive'] = handle_media_ethics_category($options, $research_categories);
    } 
    // Then check if this is a media ethics subcategory page
    else {
        $media_ethics_subcategory_archive = handle_media_ethics_subcategory($options, $research_categories);
        if ($media_ethics_subcategory_archive) {
            $context['archive'] = $media_ethics_subcategory_archive;
        }
        // Finally, if none of the special cases apply, use the default archive
        else {
            $archive = new TileArchive($options, $wp_query);
            $context['archive'] = $archive;
        }
    }
	array_unshift($templates, 'templates/archive-' . get_post_type() . '.twig');
} elseif ( is_post_type_archive() ) {
	// Archive page for a post type (ex. URLS: /research, /publications, /press, /events, etc.)
	$title = post_type_archive_title( '', false );
	$context = Timber::context(
		[
			'title' => $title,
		]
	);
	$archive = new TileArchive($options, $wp_query);
	$context['archive'] = $archive;
	array_unshift( $templates, 'templates/archive-' . get_post_type() . '.twig' );
}

$context['posts'] = Timber::get_posts();
Timber::render($templates, $context);