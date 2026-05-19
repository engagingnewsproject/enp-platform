<?php
/**
 * Template Name: Press
 * Description: A Page Template for Press
 *
 * Lists press posts by categories selected in ACF. Some Press pages also support
 * manual drag order via a page-specific relationship field (see PressPage).
 */

use Engage\Models\PressPage;
use Timber\Timber;

$context = Timber::context();
$post    = $context['post'];

$terms = get_field('posts');

if ($terms && !empty($terms)) {
    $term_ids = array_map(static function ($term) {
        return $term->term_id;
    }, $terms);

    $press_posts = PressPage::getPostsByTermIds($term_ids);

    $press_posts = PressPage::orderPressPosts($press_posts, PressPage::getManualOrder($post->ID));

    $context['press_posts'] = Timber::get_posts($press_posts);
} else {
    $context['press_posts'] = [];
}

Timber::render(['page-press.twig'], $context, ENGAGE_PAGE_CACHE_TIME);
