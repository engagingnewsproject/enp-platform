<?php
/**
 * Template Name: Tools Template
 * Description: A Page Template for tools
 */

// Code to display Page goes here...
$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
Timber::render( [ 'page-tools.twig' ], $context, ENGAGE_PAGE_CACHE_TIME );