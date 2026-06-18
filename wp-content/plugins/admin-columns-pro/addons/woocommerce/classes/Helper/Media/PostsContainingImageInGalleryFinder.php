<?php

declare(strict_types=1);

namespace ACA\WC\Helper\Media;

final class PostsContainingImageInGalleryFinder
{

    /**
     * Finds the IDs of products that reference the given attachment in their image gallery.
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

        // FIND_IN_SET splits the comma-separated meta_value and matches by exact ID,
        // avoiding LIKE false-positives such as 70650 matching when searching for 7065.
        $sql = "
            SELECT pm.post_id
            FROM $wpdb->postmeta AS pm
                JOIN $wpdb->posts AS p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_product_image_gallery'
              AND FIND_IN_SET(%d, pm.meta_value) > 0
              AND p.post_status IN ($status_placeholders)
              AND p.post_type = 'product'
        ";

        $args = array_merge([$attachment_id], $post_statuses);

        $post_ids = $wpdb->get_col($wpdb->prepare($sql, $args));

        return array_map('intval', $post_ids);
    }

}
