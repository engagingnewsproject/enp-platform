<?php

declare(strict_types=1);

namespace ACP\Helper\Media;

use AC\Helper;
use AC\View;
use DateTimeZone;

final class PostsModalRenderer
{

    private PostTypeSummary $post_type_summary;

    public function __construct(PostTypeSummary $post_type_summary)
    {
        $this->post_type_summary = $post_type_summary;
    }

    /**
     * @param int[]    $post_ids
     * @param string[] $post_statuses
     */
    public function render(int $attachment_id, array $post_ids, string $title, array $post_statuses): string
    {
        // Pass every registered post type explicitly: 'any' resolves to post types with
        // exclude_from_search=false, which omits non-public types such as WooCommerce's
        // product_variation (registered with public=false) and would silently drop those IDs.
        $posts_query = get_posts([
            'include'        => $post_ids,
            'post_type'      => get_post_types(),
            'post_status'    => $post_statuses,
            'posts_per_page' => MediaModalDefaults::LIMIT,
            'orderby'        => 'post_date',
            'order'          => 'DESC',
        ]);

        $posts = [];

        foreach ($posts_query as $post) {
            $post_title = strip_tags($post->post_title) ?: (string)$post->ID;
            $edit_link = get_edit_post_link($post->ID);

            if ($edit_link) {
                $post_title = sprintf('<a href="%s">%s</a>', $edit_link, $post_title);
            }

            $post_type_obj = get_post_type_object($post->post_type);
            $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;

            $post_status_obj = get_post_status_object($post->post_status);

            $posts[] = [
                'id'          => $post->ID,
                'post_type'   => $post_type_label,
                'post_title'  => $post_title,
                'post_status' => $post_status_obj ? $post_status_obj->label : '-',
                'post_date'   => wp_date(
                    Helper\Date::create()->get_date_format(),
                    strtotime($post->post_date),
                    new DateTimeZone('UTC')
                ) ?: '',
            ];
        }

        $view = new View([
            'title'      => $title,
            'total'      => count($post_ids),
            'image'      => wp_get_attachment_image($attachment_id, 'thumbnail', false, [
                'style' => 'max-width: 80px; max-height: 80px; width: auto; height: auto;',
            ]),
            'posts'      => $posts,
            'post_types' => $this->post_type_summary->for_ids($post_ids),
        ]);

        return $view->set_template('modal-value/posts')->render();
    }

}
