<?php

declare(strict_types=1);

namespace ACP\Helper\Media;

final class PostsContainingImageFinder
{

    /**
     * Finds the IDs of posts whose post_content references the given attachment.
     *
     * @param int      $attachment_id
     * @param string[] $post_statuses
     *
     * @return int[]
     */
    public function find(int $attachment_id, array $post_statuses): array
    {
        global $wpdb;

        if ($attachment_id <= 0 || [] === $post_statuses) {
            return [];
        }

        $like_patterns = [
            '%"id":' . $attachment_id . ',%',
            '%"id":' . $attachment_id . '}%',
            '%wp-image-' . $attachment_id . '"%',
            '%wp-image-' . $attachment_id . ' %',
            '%[caption id="attachment_' . $attachment_id . '"%',
            '%ids="' . $attachment_id . '"%',
            '%ids="' . $attachment_id . ',%',
            '%,' . $attachment_id . ',%',
            '%,' . $attachment_id . '"%',
        ];

        $status_placeholders = implode(
            ', ',
            array_fill(0, count($post_statuses), '%s')
        );
        $like_placeholders = implode(
            ' OR ',
            array_fill(0, count($like_patterns), 'p.post_content LIKE %s')
        );

        $sql = "
            SELECT DISTINCT p.ID
            FROM $wpdb->posts AS p
            WHERE p.post_status IN ($status_placeholders)
              AND p.post_type NOT IN ('revision', 'attachment')
              AND ($like_placeholders)
        ";

        $args = array_merge($post_statuses, $like_patterns);

        $post_ids = $wpdb->get_col($wpdb->prepare($sql, $args));

        return array_map('intval', $post_ids);
    }

}
