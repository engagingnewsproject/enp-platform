<?php

/**
 * Template Name: Annual Report
 * Description: A Page Template for Annual Reports
 */

// Code to display Page goes here...
$context 	= Timber::context();
$post 		= $context['post'];
$context['the_post_template'] = 'annual-report'; // Set the template name in the context 
// - see base.twig where 'the_post_template' is used to conditionally change the <main>
// element's wrapper_padding class and brand_bar visibility

Timber::render([ 'page-annual-report.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
