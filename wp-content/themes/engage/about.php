<?php
/**
 * Template Name: About Template
 * Description: About page
 */

 $context = Timber::get_context();
 $post = new TimberPost();
 $context['post'] = $post;
 Timber::render( [ 'page-about.twig' ], $context, ENGAGE_PAGE_CACHE_TIME );
