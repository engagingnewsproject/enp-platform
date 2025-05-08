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

$term_slug = 'bridging-divides'; // or get dynamically

$context['research'] = Timber::get_posts([
	'post_type' => [
		'research', 
		'blogs'
	], 
	'posts_per_page' => -1, 
	'orderby' => 'date', 
	'order' => 'DESC',
	'tax_query' => [
		'relation' => 'OR',
		[
			'taxonomy' => 'research-categories',
			'field'    => 'slug',
			'terms'    => $term_slug,
		],
		[
			'taxonomy' => 'blogs-category',
			'field'    => 'slug',
			'terms'    => $term_slug,
		],
	],
]);
Timber::render(['page-connective-democracy.twig'], $context, ENGAGE_PAGE_CACHE_TIME); 