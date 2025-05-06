<?php
/**
 * Timber class maps for terms and posts
 */

use Engage\Models\BoardMember;
use Engage\Models\Event;
use Engage\Models\ResearchArticle;
use Engage\Models\TeamArchive;
use Engage\Models\Teammate;
use Engage\Models\Press;
use Engage\Models\VerticalLanding;
use Engage\Models\URLConstructor;
use Engage\Models\Publication;
use Engage\Models\PressPage;

/**
 * Term class map
 * @link https://timber.github.io/docs/v2/guides/class-maps/
 */
add_filter('timber/term/classmap', function ($classmap) {
    $custom_classmap = [
        'research' => ResearchArticle::class,
        'team'     => TeamArchive::class,
    ];
    return array_merge($classmap, $custom_classmap);
});

/**
 * Post class map
 * @link https://timber.github.io/docs/v2/guides/class-maps/
 */
add_filter('timber/post/classmap', function ($classmap) {
    $custom_classmap = [
        'research'    => ResearchArticle::class,
        'blogs'       => ResearchArticle::class,
        'team'        => Teammate::class,
        'board'       => BoardMember::class,
        'event'       => Event::class,
        'publication' => Publication::class,
        'press'       => Press::class,
        'page'        => function (\WP_Post $post) {
            // Get the template file for the current post/page
            $template = get_page_template_slug($post->ID);
            if ($template === 'page-press.php') {
                return PressPage::class;
            } elseif ($template === 'page-landing.php') {
                return VerticalLanding::class;
            }
        },
    ];
    return array_merge($classmap, $custom_classmap);
}); 