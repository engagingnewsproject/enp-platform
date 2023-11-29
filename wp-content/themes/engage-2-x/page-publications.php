    <?php
/**
 * Template Name: Publications
 * Description: A Page Template for Publications
 */

$context = Timber::context();
$post    = $context['post'];

Timber::render([ 'page-publications.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
