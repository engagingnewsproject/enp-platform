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


$context = Timber::get_context();

$options = [];
$globals = new Engage\Managers\Globals();
$articleClass = 'Engage\Models\Article';
$teamGroups = [];

if(get_query_var('vertical_base')) {
    if(is_post_type_archive(['team'])) {
        $articleClass = 'Engage\Models\Teammate';
    }
    if(is_post_type_archive(['board'])) {
        $articleClass = 'Engage\Models\BoardMember';
    }
	$options = [
		'filters'	=> $globals->getVerticalMenu(get_query_var('verticals'))
	];
}
else if(is_post_type_archive(['research']) || is_tax('research-categories')) {
	$articleClass = 'Engage\Models\ResearchArticle';
	$options = [
		'filters'	=> $globals->getResearchMenu()
	];
}
else if(is_post_type_archive(['announcement']) || is_tax('announcement-category')) {
	$options = [
		'filters'	=> $globals->getAnnouncementMenu()
	];
}
else if(is_post_type_archive(['case-study']) || is_tax('case-study-category')) {
	$options = [
		'filters'	=> $globals->getCaseStudyMenu()
	];
}
else if(is_post_type_archive(['team']) || is_tax('team_category')) {
    $articleClass = 'Engage\Models\Teammate';
  	$options = [
  		'filters'	=> $globals->getTeamMenu()
  	];
}
else if(is_post_type_archive(['board']) || is_tax('board_category')) {
    $articleClass = 'Engage\Models\BoardMember';
  	$options = [
  		'filters'	=> $globals->getBoardMenu()
  	];
}
else if(is_post_type_archive(['tribe_events'])) {
	$articleClass = 'Engage\Models\Event';
	$options = [
		'filters'	=> $globals->getEventMenu()
	];
}

// build intro
$query = false;
if (is_post_type_archive(['team']) || is_tax('team_category')) {
  $archive = new Engage\Models\TeamArchive($options, $query, $articleClass);
}
else {
  $archive = new Engage\Models\TileArchive($options, $query, $articleClass);
}
$context['archive'] = $archive;

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
      
    $thumbID = get_field('category_featured_image', "research-categories_" . $category['ID']);
      if($thumbID) {
          // set the thumbnail
          $researchCategories[$key]["thumbnail"] = new TimberImage($thumbID);
          $researchCategories[$key]["preview"] = term_description($category['ID']);
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
