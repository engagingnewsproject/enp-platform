    <?php
/**
 * Template Name: Publications
 * Description: A Page Template for Publications
 */

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

    $publication_posts = get_posts([
        'post_type' => 'publication',
        'numberposts' => -1,
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'publication_date',
                'compare' => 'EXISTS',
                'type' => 'DATE'
            ],
            [
                'key' => 'publication_date',
                'compare' => 'NOT EXISTS'
            ]
        ],
        'orderby' => [
            'publication_date' => 'DESC',
            'publisher' => 'ASC'
        ],
        'tax_query' => [
            [
                'taxonomy' => 'publication-categories',
                'field'    => 'term_id',
                'terms'    => $term_ids
            ]
        ]
    ]);

    // Convert to Timber posts
    $context['publication_posts'] = Timber::get_posts($publication_posts);
} else {
    $context['publication_posts'] = [];
}

Timber::render([ 'page-publications.twig' ], $context, ENGAGE_PAGE_CACHE_TIME);
