<?php
/**
 * Template Name: Landing
 * Description: A Page Template for Vertical Landing pages
 */

// Code to display Page goes here...
$context = Timber::context();
$post    = $context['post'];

// $context['newsletter'] = Timber::get_widgets('newsletter');
Timber::render( [ 'page/landing.twig', 'page.twig' ], $context, ENGAGE_PAGE_CACHE_TIME );
