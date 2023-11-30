<?php

/**
 * Template Name: Tips Sheet
 * Description: A Page Template for Tips Sheet
 */

// Code to display Page goes here...
$context = Timber::context();
$post    = $context['post'];

Timber::render(['page-tips-sheet.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
