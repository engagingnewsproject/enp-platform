<?php

/**
 * Template Name: Video Grid Template
 * Description: A Page Template for Video Grid
 */

// Code to display Page goes here...
$context = Timber::get_context();
$post = new TimberPost();

$context['post'] = $post;
$cover_image_id = $post->cover_image;
$context['cover_image'] = new Timber\Image($cover_image_id);
Timber::render(['page-video-grid.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
