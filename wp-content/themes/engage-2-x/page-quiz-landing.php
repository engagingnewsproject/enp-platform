<?php

/**
 * Template Name: Quiz Landing
 * Description: A Page Template
 * The template for displaying the Quiz Landing page.
 */

$context = Timber::context();
$post    = $context['post'];

Timber::render(['page/quiz-landing.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
