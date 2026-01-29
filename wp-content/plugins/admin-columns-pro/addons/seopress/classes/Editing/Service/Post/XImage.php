<?php

declare(strict_types=1);

namespace ACA\SeoPress\Editing\Service\Post;

use ACP\Editing\Service;
use ACP\Editing\View;

class XImage implements Service
{

    public function get_view(string $context): ?View
    {
        return new View\Image();
    }

    public function get_value(int $id)
    {
        return get_post_meta($id, '_seopress_social_twitter_img_attachment_id', true);
    }

    public function update(int $id, $data): void
    {
        update_post_meta($id, '_seopress_social_twitter_img_attachment_id', $data);
        update_post_meta($id, '_seopress_social_twitter_img', wp_get_attachment_url($data));
    }
}