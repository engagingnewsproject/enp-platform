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

if(get_query_var('vertical_base')) {
    if(is_post_type_archive(['team'])) {
        $articleClass = 'Engage\Models\Teammate';
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
} else if(is_post_type_archive(['tribe_events'])) {
	$articleClass = 'Engage\Models\Event';
	$options = [
		'filters'	=> $globals->getEventMenu()
	];
}

// build intro
$query = false;
$archive = new Engage\Models\TileArchive($options, $query, $articleClass);
$context['archive'] = $archive;

Timber::render( ['archive.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
