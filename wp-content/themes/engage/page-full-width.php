<?php

/**
 * Template Name: Page Full Width
 * Description: Full Width Page Template
 */

use Timber\PostQuery;

// Code to display Page goes here...
$context = Timber::get_context();
$post = new TimberPost();
if (isset($post->header_image) && strlen($post->header_image)) {
  $post->header_image = new Timber\Image($post->header_image);
}
$context['post'] = $post;
// $context['site_copyright_info'] = get_field('alternate_logo', 'options');
Timber::render(['page-full-width.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
