<?php

/**
 * Template Name: Quiz Landing
 * Description: A Page Template
 */
/**
 * The template for displaying the Quiz Landing page.
 *
 **/

$context = Timber::get_context();
$post = new TimberPost();

$context['post'] = $post;
$context['site_copyright_info'] = get_field('alternate_logo', 'options');

Timber::render(['page-quiz-landing.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
