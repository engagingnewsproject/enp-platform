<?php

/**
 * Template Name: Connective Democracy Template
 * Description: A Page Template for Connective Democracy
 */
use Timber\PostQuery;

$context = Timber::get_context();
$post = new TimberPost();
if (isset($post->header_image) && strlen($post->header_image)) {
    $post->header_image = new Timber\Image($post->header_image);
}
$context['post'] = $post;

// get newsroom resource posts from the relationship field
$newsroom_resource_posts = get_field('newsroom_resource_posts');
$context['newsroom_resource_posts'] = $newsroom_resource_posts;
// END newsroom resource posts

$context['research'] = new PostQuery(
    ['post_type' => 'research', 
    'posts_per_page' => -1, 
    'verticals' => 'bridging-divides', 
    'orderby' => 'date', 
    'order' => 'DESC'],
    'Engage\Models\ResearchArticle'
);
Timber::render(['page-connective-democracy.twig'], $context, ENGAGE_PAGE_CACHE_TIME); 