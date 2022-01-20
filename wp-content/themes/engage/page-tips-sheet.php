<?php

/**
 * Template Name: Tips Sheet Template
 * Description: A Page Template for Tips Sheet
 */

// Code to display Page goes here...
$context = Timber::get_context();
$post = new TimberPost();

$context['post'] = $post;
$context['site_copyright_info'] = get_field('alternate_logo', 'options');


Timber::render(['page-tips-sheet.twig'], $context, ENGAGE_PAGE_CACHE_TIME);