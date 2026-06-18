<?php

declare(strict_types=1);

namespace ACA\ACF\Helper\Media;

use AC\Acf\FieldGroupCache;

final class PostsContainingImageInAcfFinder
{

    private FieldGroupCache $field_group_cache;

    public function __construct(FieldGroupCache $field_group_cache)
    {
        $this->field_group_cache = $field_group_cache;
    }

    /**
     * Finds posts whose ACF image/file/gallery fields reference the attachment.
     *
     * @param int      $attachment_id
     * @param string[] $post_statuses
     *
     * @return array<int, string[]> post_id => matched meta_keys
     */
    public function find(int $attachment_id, array $post_statuses): array
    {
        if ($attachment_id <= 0 || [] === $post_statuses) {
            return [];
        }

        $keys = $this->get_meta_keys();

        if ([] === $keys['image_file'] && [] === $keys['gallery']) {
            return [];
        }

        $results = [];

        foreach ($this->query_image_file($attachment_id, $keys['image_file'], $post_statuses) as $row) {
            $results[(int)$row->post_id][] = (string)$row->meta_key;
        }

        foreach ($this->query_gallery($attachment_id, $keys['gallery'], $post_statuses) as $row) {
            $results[(int)$row->post_id][] = (string)$row->meta_key;
        }

        foreach ($results as $post_id => $meta_keys) {
            $results[$post_id] = array_values(array_unique($meta_keys));
        }

        return $results;
    }

    /**
     * @return array{image_file: string[], gallery: string[]}
     */
    private function get_meta_keys(): array
    {
        $by_type = $this->field_group_cache->get_meta_keys_grouped_by_type();

        $image_file = array_merge(
            $by_type['image'] ?? [],
            $by_type['file'] ?? []
        );

        return [
            'image_file' => array_values(array_unique($image_file)),
            'gallery'    => array_values(array_unique($by_type['gallery'] ?? [])),
        ];
    }

    /**
     * @param string[] $meta_keys
     * @param string[] $post_statuses
     *
     * @return object[]
     */
    private function query_image_file(int $attachment_id, array $meta_keys, array $post_statuses): array
    {
        if ([] === $meta_keys) {
            return [];
        }

        global $wpdb;

        $meta_placeholders = implode(', ', array_fill(0, count($meta_keys), '%s'));
        $status_placeholders = implode(', ', array_fill(0, count($post_statuses), '%s'));

        $sql = "
            SELECT pm.post_id, pm.meta_key
            FROM $wpdb->postmeta pm
            INNER JOIN $wpdb->posts p ON p.ID = pm.post_id
            WHERE pm.meta_key IN ($meta_placeholders)
              AND pm.meta_value = %d
              AND p.post_status IN ($status_placeholders)
              AND p.post_type NOT IN ('revision', 'attachment')
        ";

        $args = array_merge($meta_keys, [$attachment_id], $post_statuses);

        return (array)$wpdb->get_results($wpdb->prepare($sql, $args));
    }

    /**
     * @param string[] $meta_keys
     * @param string[] $post_statuses
     *
     * @return object[]
     */
    private function query_gallery(int $attachment_id, array $meta_keys, array $post_statuses): array
    {
        if ([] === $meta_keys) {
            return [];
        }

        global $wpdb;

        $id_string = (string)$attachment_id;
        $needle = sprintf('s:%d:"%s"', strlen($id_string), $id_string);
        $like_pattern = '%' . $wpdb->esc_like($needle) . '%';
        $meta_placeholders = implode(', ', array_fill(0, count($meta_keys), '%s'));
        $status_placeholders = implode(', ', array_fill(0, count($post_statuses), '%s'));

        $sql = "
            SELECT pm.post_id, pm.meta_key
            FROM $wpdb->postmeta pm
            INNER JOIN $wpdb->posts p ON p.ID = pm.post_id
            WHERE pm.meta_key IN ($meta_placeholders)
              AND pm.meta_value LIKE %s
              AND p.post_status IN ($status_placeholders)
              AND p.post_type NOT IN ('revision', 'attachment')
        ";

        $args = array_merge($meta_keys, [$like_pattern], $post_statuses);

        return (array)$wpdb->get_results($wpdb->prepare($sql, $args));
    }

}
