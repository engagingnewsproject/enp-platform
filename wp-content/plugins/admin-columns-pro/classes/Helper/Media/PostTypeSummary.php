<?php

declare(strict_types=1);

namespace ACP\Helper\Media;

final class PostTypeSummary
{

    /**
     * @param int[] $post_ids
     *
     * @return array<int, array{link: string, post_type: string, count: string}>
     */
    public function for_ids(array $post_ids): array
    {
        global $wpdb;

        if ([] === $post_ids) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($post_ids), '%d'));
        $sql = "SELECT post_type, COUNT(*) AS total
                FROM $wpdb->posts
                WHERE ID IN ($placeholders)
                GROUP BY post_type";

        $rows = $wpdb->get_results($wpdb->prepare($sql, $post_ids));

        $items = [];

        foreach ($rows as $row) {
            $post_type_obj = get_post_type_object($row->post_type);

            $items[] = [
                'link'      => add_query_arg(['post_type' => $row->post_type], admin_url('edit.php')),
                'post_type' => $post_type_obj ? $post_type_obj->labels->singular_name : $row->post_type,
                'count'     => number_format_i18n((int)$row->total),
            ];
        }

        return $items;
    }

}
