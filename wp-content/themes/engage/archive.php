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
if(get_query_var('vertical_base')) {
	$options = [
		'filters'	=> $globals->getVerticalMenu(get_query_var('verticals'))
	];
}
else if(is_post_type_archive(['research']) || is_tax('research-categories')) {
	$options = [
		'filters'	=> $globals->getResearchMenu()
	];
} else if(is_post_type_archive(['team']) || is_tax('team_category')) {
	$globals = new Engage\Managers\Globals();
	$options = [
		'filters'	=> $globals->getTeamMenu()
	];
} 

// build intro
$archive = new Engage\Models\TileArchive($options);
$context['archive'] = $archive;

Timber::render( ['archive.twig'], $context );
