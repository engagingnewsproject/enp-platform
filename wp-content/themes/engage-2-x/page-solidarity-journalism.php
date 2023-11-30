<?php

/**
 * Template Name: Solidarity Journalism
 * Description: A Page Template for Solidarity Journalism
 */

$context	= Timber::context();
$post		= $context['post'];

// get newsroom resource posts from the relationship field
$resource_posts = get_field('resource_posts');
$context['resource_posts'] = $resource_posts;
// END newsroom resource posts

Timber::render(['page-solidarity-journalism.twig'], $context, ENGAGE_PAGE_CACHE_TIME); 