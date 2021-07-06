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
$context['research'] = new PostQuery(
    ['post_type' => 'research', 'posts_per_page' => 6, 'verticals' => 'bridging-divides', 'orderby' => 'date', 'order' => 'DESC'],
    'Engage\Models\ResearchArticle'
);
Timber::render(['page-connective-democracy.twig'], $context, ENGAGE_PAGE_CACHE_TIME);