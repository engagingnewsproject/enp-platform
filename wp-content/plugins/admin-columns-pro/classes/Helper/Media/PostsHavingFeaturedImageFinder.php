<?php

declare(strict_types=1);

namespace ACP\Helper\Media;

final class PostsHavingFeaturedImageFinder
{

    /**
     * Finds the IDs of posts that use the given attachment as their featured image.
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

        $status_placeholders = implode(', ', array_fill(0, count($post_statuses), '%s'));

        $sql = "
            SELECT pm.post_id
            FROM $wpdb->postmeta AS pm
                JOIN $wpdb->posts AS p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_thumbnail_id'
              AND pm.meta_value = %d
              AND p.post_status IN ($status_placeholders)
        ";

        $args = array_merge([$attachment_id], $post_statuses);

        $post_ids = $wpdb->get_col($wpdb->prepare($sql, $args));

        return array_map('intval', $post_ids);
    }

}
