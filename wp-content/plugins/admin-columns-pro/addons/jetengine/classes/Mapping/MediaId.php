<?php

declare(strict_types=1);

namespace ACA\JetEngine\Mapping;

final class MediaId
{

    public static function to_array($id): array
    {
        return [
            'url' => wp_get_attachment_url($id),
            'id'  => $id,
        ];
    }

    public static function from_array($entry): ?array
    {
        return is_array($entry) && isset($entry['id'])
            ? $entry['id']
            : null;
    }

    public static function from_url($url): ?int
    {
        return $url
            ? attachment_url_to_postid($url)
            : null;
    }

    public static function to_url($id): ?string
    {
        return wp_get_attachment_url($id) ?: null;
    }

}