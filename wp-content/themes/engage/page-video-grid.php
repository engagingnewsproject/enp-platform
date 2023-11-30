<?php

/**
 * Template Name: Video Grid
 * Description: A Page Template for Video Grid
 */

// Code to display Page goes here...
$context = Timber::context();

$timber_post     = Timber::get_post();
$context['post'] = $timber_post;

Timber::render(['page-video-grid.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
