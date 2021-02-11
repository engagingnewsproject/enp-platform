<?php
/**
 * Template Name: Tool Template
 * Description: A Page Template for tools
 */

// Code to display Page goes here...
$context = Timber::get_context();
$post = new Engage\Models\ToolsMenu();
$context['post'] = $post;
$context['newsletter'] = Timber::get_widgets('newsletter');
Timber::render( [ 'page-tools.twig' ], $context, ENGAGE_PAGE_CACHE_TIME );
