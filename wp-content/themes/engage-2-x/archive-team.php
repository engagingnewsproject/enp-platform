<?php

/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 */



$context = Timber::context();
global $wp_query;
use Engage\Models\TileArchive;

use Engage\Models\TeamArchive;

$globals = new Engage\Managers\Globals();


$options = [
    'categories' => ['principal-investigators', 'staff', 'student-researchers', 'research-collaborators', 'board', 'alumni']
];
$teamGroups = [];


if ((
    is_post_type_archive(['team']) || 
    is_tax('team_category'))) {
      
        $archive = new TeamArchive( $wp_query, $options );
        $context['archive'] = $archive;
        $context['filtered_posts'] = $archive->getPosts();
        $context['category_titles'] = ['principal-investigators' =>"Principal Investigators", 'staff'=>"Staff", 'student-researchers'=>"Student Researchers",'research-collaborators'=>"Research Collaborators", 'board'=>"Board", 'alumni' =>"Alumni"];

        $context['categories'] = ['principal-investigators', 'staff', 'student-researchers', 'research-collaborators', 'board', 'alumni'];
}
Timber::render(['page-team.twig'], $context);