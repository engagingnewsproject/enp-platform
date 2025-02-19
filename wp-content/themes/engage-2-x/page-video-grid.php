<?php

/**
 * Template Name: Video Grid
 * Description: A Page Template for Video Grid
 */

$context = Timber::context();
$post = $context['post'];

Timber::render(['page/video-grid.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
