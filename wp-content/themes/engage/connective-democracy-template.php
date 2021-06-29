<?php

/**
 * Template Name: Connective Democracy Template
 * Description: A Page Template for Connective Democracy
 */

// Code to display Page goes here...
$context = Timber::get_context();
$post = new TimberPost();
if (isset($post->header_image) && strlen($post->header_image)) {
    $post->header_image = new Timber\Image($post->header_image);
}
$context['post'] = $post;
Timber::render(['page-connective-democracy.twig'], $context, ENGAGE_PAGE_CACHE_TIME);