<?php

/**
 * Template Name: Solidarity Journalism Template
 * Description: A Page Template for Solidarity Journalism
 */
use Timber\PostQuery;

$context = Timber::get_context();
$post = new TimberPost();
if (isset($post->header_image) && strlen($post->header_image)) {
    $post->header_image = new Timber\Image($post->header_image);
}
$context['post'] = $post;

// get newsroom resource posts from the relationship field
$resource_posts = get_field('resource_posts');
$context['resource_posts'] = $resource_posts;
// END newsroom resource posts

Timber::render(['page-solidarity-journalism.twig'], $context, ENGAGE_PAGE_CACHE_TIME); 