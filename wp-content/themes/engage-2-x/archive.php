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

global $wp_query;

use Engage\Models\TileArchive;
use Engage\Models\TeamArchive;

$globals = new Engage\Managers\Globals();
$options = [];
$teamGroups = [];

// Set options for sidebar filters
if(get_query_var('vertical_base')) {
	$options = [
		'filters'	=> $globals->getVerticalMenu(get_query_var('verticals'))
	];
} else if (is_post_type_archive(['research']) || is_tax('research-categories')) {
	$options = [
		'filters'	=> $globals->getResearchMenu(),
	];	
} else if(is_post_type_archive(['announcement']) || is_tax('announcement-category')) {
	$options = [
		'filters'	=> $globals->getAnnouncementMenu()
	];
} else if(is_post_type_archive(['blogs']) || is_tax('blogs-category')) {
	$options = [
		'filters'	=> $globals->getBlogMenu()
	];
} else if(is_post_type_archive(['team']) || is_tax('team_category')) {
	$options = [
		'filters'	=> $globals->getTeamMenu()
	];
} else if(is_post_type_archive(['board']) || is_tax('board_category')) {
	$options = [
		'filters'	=> $globals->getBoardMenu()
	];
} else if(is_post_type_archive(['tribe_events'])) {
	$options = [
		'filters'	=> $globals->getEventMenu()
	];
}
// //
// Build intro
// //

if ((
		is_post_type_archive(['team']) || 
		is_tax('team_category')) || 
		(
			is_post_type_archive(['board']) || 
			is_tax('board_category')
	)) {
	$archive = new TeamArchive( $wp_query, $options );
} else {
	$archive = new TileArchive(  $options, $wp_query );
}
$context['archive'] = $archive; // Sidebar filters

if(preg_match('/\/announcement\/([^\/]*\/)?([^\/]*(\/))?/', $_SERVER['REQUEST_URI'])) {
  $context['archive']['announcement'] = True;
}

if(get_query_var('verticals') == 'media-ethics' && $_SERVER['REQUEST_URI'] == '/vertical/media-ethics/') {
  // get media ethics vertical term
  $mediaEthicsTerm = get_term_by('slug', 'media-ethics', 'verticals');
	
  $researchTiles = [];
  // Get media ethics research categories
  $researchCategories = $options['filters']['terms']['research']['terms'];
	foreach($researchCategories as $key => $category) {
		// Get the category image ID from the ACF image custom field
		// IMPORTANT: the ACF image field needs to return the image ID
		$thumbID = get_field('category_featured_image', "research-categories_" . $category['ID']);

		if($thumbID) {
			// set the thumbnail
			$researchCategories[$key]["thumbnail"] = Timber::get_image($thumbID);
			// set the description
			$researchCategories[$key]["excerpt"] = term_description($category['ID']);
			// add function to tiles
			$researchCategories[$key]["vertical"] =  $mediaEthicsTerm;
			// add it to the research tiles
			$researchTiles[] = $researchCategories[$key];
		}
	}
  // set the posts as the research tiles that have thumbnails
  $context['archive']['posts'] = $researchTiles;
}

Timber::render( ['archive.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
			