<?php

/**
 * Template Name: Public Interest Standard Template
 * Description: A Page Template for the Public Interest Page
 */

// Code to display Page goes here...
$context = Timber::get_context();
$post = new TimberPost();

$context['post'] = $post;
// $context['site_copyright_info'] = get_field('alternate_logo', 'options');
if (isset($post->gif1) && strlen($post->gif1)) {
  $post->gif1 = new Timber\Image($post->gif1);
}
if (isset($post->gif2) && strlen($post->gif2)) {
  $post->gif2 = new Timber\Image($post->gif2);
}
if (isset($post->gif3) && strlen($post->gif3)) {
  $post->gif3 = new Timber\Image($post->gif3);
}
if (isset($post->gif4) && strlen($post->gif4)) {
  $post->gif4 = new Timber\Image($post->gif4);
}
if (isset($post->gif5) && strlen($post->gif5)) {
  $post->gif5 = new Timber\Image($post->gif5);
}
Timber::render(['page-public-interest-standard.twig'], $context, ENGAGE_PAGE_CACHE_TIME);

