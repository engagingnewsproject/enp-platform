<?php

declare(strict_types=1);

namespace ACA\ACF\Value\ExtendedValue\Media;

use AC\Column;
use AC\Helper;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use AC\View;
use ACA\ACF\Helper\Media\PostsContainingImageInAcfFinder;
use ACP\Helper\Media\MediaModalDefaults;
use ACP\Helper\Media\PostTypeSummary;
use DateTimeZone;

class PostsContainingImageInAcf implements ExtendedValue
{

    private const NAME = 'posts-containing-image-in-acf';

    private PostsContainingImageInAcfFinder $finder;

    private PostTypeSummary $post_type_summary;

    public function __construct(PostsContainingImageInAcfFinder $finder, PostTypeSummary $post_type_summary)
    {
        $this->finder = $finder;
        $this->post_type_summary = $post_type_summary;
    }

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return new ExtendedValueLink($label, $id, self::NAME, ['class' => '-w-xlarge']);
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $attachment_id = (int)$id;

        $post_id_to_fields = $this->finder->find($attachment_id, MediaModalDefaults::POST_STATUSES);

        if ([] === $post_id_to_fields) {
            return __('No items', 'codepress-admin-columns');
        }

        $post_ids = array_keys($post_id_to_fields);

        $posts_query = $this->fetch_posts($post_ids);

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
                'acf_fields'  => $post_id_to_fields[$post->ID] ?? [],
            ];
        }

        $total = count($post_ids);

        $title = sprintf(
            _n(
                '%d post references this item via an ACF field',
                '%d posts reference this item via an ACF field',
                $total,
                'codepress-admin-columns'
            ),
            $total
        );

        $view = new View([
            'title'      => $title,
            'total'      => $total,
            'image'      => wp_get_attachment_image($attachment_id, 'thumbnail', false, [
                'style' => 'max-width: 80px; max-height: 80px; width: auto; height: auto;',
            ]),
            'posts'      => $posts,
            'post_types' => $this->post_type_summary->for_ids($post_ids),
        ]);

        return $view->set_template('modal-value/posts-acf')->render();
    }

    /**
     * Fetch post rows directly via $wpdb to avoid WP_Query's post_type="any"
     * silently excluding CPTs registered with exclude_from_search=true.
     *
     * @param int[] $post_ids
     *
     * @return object[]
     */
    private function fetch_posts(array $post_ids): array
    {
        global $wpdb;

        if ([] === $post_ids) {
            return [];
        }

        $id_placeholders = implode(', ', array_fill(0, count($post_ids), '%d'));
        $status_placeholders = implode(', ', array_fill(0, count(MediaModalDefaults::POST_STATUSES), '%s'));

        $sql = "
            SELECT ID, post_title, post_type, post_status, post_date
            FROM $wpdb->posts
            WHERE ID IN ($id_placeholders)
              AND post_status IN ($status_placeholders)
            ORDER BY post_date DESC
            LIMIT %d
        ";

        $args = array_merge($post_ids, MediaModalDefaults::POST_STATUSES, [MediaModalDefaults::LIMIT]);

        return (array)$wpdb->get_results($wpdb->prepare($sql, $args));
    }

}
