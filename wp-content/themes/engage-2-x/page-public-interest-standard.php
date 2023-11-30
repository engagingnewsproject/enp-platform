<?php

/**
 * Template Name: Public Interest Standard
 * Description: A Page Template for the Public Interest Page
 */

// Code to display Page goes here...
$context = Timber::context();
$post    = $context['post'];

Timber::render(['page-public-interest-standard.twig'], $context, ENGAGE_PAGE_CACHE_TIME);

