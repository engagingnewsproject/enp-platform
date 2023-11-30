<?php

/**
 * Template Name: Annual Report
 * Description: A Page Template for Annual Reports
 */

// Code to display Page goes here...
$context 	= Timber::context();
$post 		= $context['post'];

Timber::render([ 'page-annual-report.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
