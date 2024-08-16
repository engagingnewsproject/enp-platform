<?php

/**
 * Template Name: Annual Report
 * Description: A Page Template for Annual Reports
 */

// Code to display Page goes here...
$context 	= Timber::context();
$post 		= $context['post'];
$context['the_post_template'] = 'annual-report'; // Set the template name in the context

Timber::render([ 'page-annual-report.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
