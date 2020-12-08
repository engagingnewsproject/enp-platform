<?php
/**
 * Template Name: Tool Template
 * Description: A Page Template for tools
 */

// Code to display Page goes here...
$context = Timber::get_context();
$post = new TimberPost();
$context['post'] = $post;
Timber::render( [ 'page-tool.twig' ], $context, ENGAGE_PAGE_CACHE_TIME );
