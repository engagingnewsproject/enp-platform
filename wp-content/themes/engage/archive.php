<?php
/**
* The template for displaying Archive pages.
*
* Used to display archive-type pages if nothing more specific matches a query.
* For example, puts together date-based pages if no date.php file exists.
*
* Learn more: http://codex.wordpress.org/Template_Hierarchy
*
* Methods for TimberHelper can be found in the /lib sub-directory
*
* @package  WordPress
* @subpackage  Timber
* @since   Timber 0.2
*/
$context = Timber::context();

// MIGHT NEED THIS
// global $wp_query;

use Engage\Models\TileArchive;

$globals = new Engage\Managers\Globals();
$teamGroups = [];

// Change arguments for default query
// LINK: https://timber.github.io/docs/v2/guides/context/#change-arguments-for-default-query
$options = [
	'merge_default' => true,
];

// Filter options
$options_filters = [
	'filters'	=> $globals->getVerticalMenu(get_query_var('verticals')),
];

// New TileArchive class
$archive = new TileArchive($wp_query, $options_filters);
$context['archive'] = $archive;

// MIGHT NEED THIS
// $context['archive']['posts'] = Timber::get_posts($wp_query, $options);
if(get_query_var('verticals') == 'media-ethics' && $_SERVER['REQUEST_URI'] == '/vertical/media-ethics/') {
	// get media ethics vertical term
	$mediaEthicsTerm = get_term_by('slug', 'media-ethics', 'verticals');
	$researchTiles = [];
	// Get media ethics research categories
	$researchCategories = $options_filters['filters']['terms']['research']['terms'];
	foreach($researchCategories as $key => $category) {
		// var_dump( $category['ID'] );
		
		// var_dump( $key );
		// $thumbID = get_field('category_featured_image', "research-categories_" . $category['ID']);
		
		// $thumbID = $category->meta('category_featured_image', "research-categories_" . $category['ID']);
		// var_dump( $category['ID'], $thumbID );
		// // Timber defaults
		// $term = Timber::get_term();
		// $my_custom_field = $post->raw_meta( 'category_featured_image' );
		
		// My tests
		// TODO: HOW YOU DO IT: https://stackoverflow.com/questions/42725959/timber-twig-and-acf-get-image-from-custom-field-on-taxonomy-page
		$term = Timber::get_term($category['ID']);
		
		$thumbID = $term->meta( 'category_featured_image' );
		var_dump( $thumbID );
		$cover_image_id = $context['term']->category_featured_image;

		$context['cover_image'] = Timber::get_post($cover_image_id);
		// if($thumbID) {
		// 	// set the thumbnail
		// 	$cover_image_id = $context['post']->cover_image;

		// 	$context['cover_image'] = Timber::get_post($cover_image_id);
			
		// 	$researchCategories[$key]["thumbnail"] = new TimberImage($thumbID);
		// 	var_dump( $researchCategories[$key]["thumbnail"] );
		// 	$researchCategories[$key]["preview"] = term_description($category['ID']);
		// 	// add function to tiles
		// 	$researchCategories[$key]["vertical"] =  $mediaEthicsTerm;
		// 	// add it to the research tiles
		// 	$researchTiles[] = $researchCategories[$key];
		// }
	}
	// set the posts as the research tiles that have thumbnails
	$context['archive']['posts'] = $researchTiles;
}
Timber::render( ['archive.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
