    <?php
/**
 * Template Name: Publications
 * Description: A Page Template for Publications
 */

$context = Timber::context();

$timber_post     = Timber::get_post();
$context['post'] = $timber_post;

Timber::render([ 'page-publications.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
