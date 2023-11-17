    <?php
/**
 * Template Name: Publications Template
 * Description: A Page Template for Publications
 */

// Code to display Page goes here...
$context = Timber::context();

$post = new Engage\Models\Publications();

$context['post'] = $post;
Timber::render([ 'page-publications.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
