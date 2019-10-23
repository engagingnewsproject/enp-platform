<?php
/**
 * Template Name: About Template
 * Description: About page
 */
 use Timber\PostQuery;

 $context = Timber::get_context();
 $post = new TimberPost();
 $context['post'] = $post;
 $context['about'] = new PostQuery(
     ['post_type' => 'funders', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'],
     'Engage\Models\Funder'
 );
 $context['newsletter'] = Timber::get_widgets('newsletter');
 Timber::render( [ 'page-about.twig' ], $context, ENGAGE_PAGE_CACHE_TIME );
