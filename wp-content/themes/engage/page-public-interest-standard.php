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
Timber::render(['page-public-interest-standard.twig'], $context, ENGAGE_PAGE_CACHE_TIME);