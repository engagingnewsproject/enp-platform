<?php
/**
 * Template Name: Tools
 * Description: A Page Template for Tools page
 */

$context 	= Timber::context();
$post 		= $context['post'];

Timber::render([ 'page/tools.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
