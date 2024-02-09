<?php

/**
 * Template Name: Full Width
 * Description: Full Width Page Template
 */

// Code to display Page goes here...
$context = Timber::context();
$post    = $context['post'];

Timber::render(['page-full-width.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
