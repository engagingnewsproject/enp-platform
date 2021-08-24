<?php

/**
 * Template Name: Public Interest Standard Template
 * Description: A Page Template for the Public Interest Page
 */

// Code to display Page goes here...
$context = Timber::get_context();
$post = new TimberPost();

$context['post'] = $post;
$context['site_copyright_info'] = get_field('alternate_logo', 'options');
if (isset($post->gif1) && strlen($post->gif1)) {
  $post->gif1 = new Timber\Image($post->gif1);
}
if (isset($post->title_gif2) && strlen($post->title_gif2)) {
  $post->title_gif2 = new Timber\Image($post->title_gif2);
}
Timber::render(['page-public-interest-standard.twig'], $context, ENGAGE_PAGE_CACHE_TIME);

