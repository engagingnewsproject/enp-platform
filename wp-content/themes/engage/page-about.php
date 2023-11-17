<?php
/**
 * Template Name: About
 * Description: A Page Template for About Us page
 */

$context = Timber::context();

$timber_post     = Timber::get_post();
$context['post'] = $timber_post;

Timber::render([ 'page-about.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
