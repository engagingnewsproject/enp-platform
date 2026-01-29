<?php

declare(strict_types=1);

namespace ACA\RankMath\Editing\Storage\Post;

use ACP\Editing\Storage;

final class FacebookImage implements Storage
{

    public function get(int $id)
    {
        return get_post_meta($id, 'rank_math_facebook_image_id', true);
    }

    public function update(int $id, $data): bool
    {
        update_post_meta($id, 'rank_math_facebook_image_id', $data);
        update_post_meta($id, 'rank_math_facebook_image', wp_get_attachment_url($data));

        return true;
    }

}