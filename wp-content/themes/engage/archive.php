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
if(is_post_type_archive(['research']) || is_tax('research-categories')) {
	$globals = new Engage\Managers\Globals();
	$options = [
		'taxonomies' => ['vertical', 'research-categories'], 
		'taxonomyStructure' => 'vertical', 
		'postTypes' => ['research'],
		'filters'	=> $globals->getResearchMenu()
	];
} 
elseif(is_tax('verticals')) {
	$options = [
		'taxonomies' => ['research-categories', 'team_category', 'category'], 
		'postTypes' => ['research', 'team', 'post']
	];
}


// build intro
$archive = new Engage\Models\TileArchive($options);
$context['archive'] = $archive;

Timber::render( ['archive.twig'], $context );
