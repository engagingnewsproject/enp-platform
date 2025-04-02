<?php
/**
 * Template Name: Press
 * Description: A Page Template for Press
 */

use Timber\Timber;

$context = Timber::context();
$post    = $context['post'];

// Get the term objects from ACF field
$terms = get_field('posts'); // This will return term objects since that's selected in ACF

// Get posts from those terms if terms exist
if ($terms && !empty($terms)) {
    // Extract term IDs from term objects
    $term_ids = array_map(function($term) {
        return $term->term_id;
    }, $terms);

    $press_posts = get_posts([
        'post_type' => 'press',
        'numberposts' => -1,
        'tax_query' => [
            [
                'taxonomy' => 'press-categories',
                'field'    => 'term_id',
                'terms'    => $term_ids
            ]
        ]
    ]);

    // Convert to Timber posts
    $context['press_posts'] = Timber::get_posts($press_posts);
} else {
    $context['press_posts'] = [];
}

Timber::render(['page-press.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
