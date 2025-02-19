<?php

/**
 * Template Name: Connective Democracy
 * Description: A Page Template for Connective Democracy
 */

$context    = Timber::context();
$post       = $context['post'];

// get newsroom resource posts from the relationship field
$newsroom_resource_posts = get_field('newsroom_resource_posts');
$context['newsroom_resource_posts'] = $newsroom_resource_posts;
// END newsroom resource posts

$context['research'] = Timber::get_posts([
	'post_type' => [
		'research', 
		'blogs'
	], 
	'posts_per_page' => -1, 
	'verticals' => 'bridging-divides', 
	'orderby' => 'date', 
	'order' => 'DESC'],
);
Timber::render(['page/connective-democracy.twig'], $context, ENGAGE_PAGE_CACHE_TIME); 