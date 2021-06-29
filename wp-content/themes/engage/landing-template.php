<?php
/**
 * Template Name: Landing Template
 * Description: A Page Template for Vertical Landing pages
 */

// Code to display Page goes here...
$context = Timber::get_context();
$post = new Engage\Models\VerticalLanding();
$context['post'] = $post;
$context['newsletter'] = Timber::get_widgets('newsletter');
Timber::render( [ 'page-' . $post->post_name . '.twig', 'page-landing.twig' ], $context, ENGAGE_PAGE_CACHE_TIME );
