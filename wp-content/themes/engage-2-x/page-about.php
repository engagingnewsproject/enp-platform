<?php
/**
 * Template Name: About
 * Description: A Page Template for About Us page
 */

$context 	= Timber::context();
$post 		= $context['post'];

Timber::render([ 'page-about.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
