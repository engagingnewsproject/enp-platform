<?php

namespace ACP\Editing\Storage\Post;

use ACP\Editing\Storage;

class Sticky implements Storage
{

    private ?array $stickies = null;

    private function is_sticky(int $id): bool
    {
        if (null === $this->stickies) {
            $stickies = get_option('sticky_posts', []) ?: [];

            $this->stickies = array_map('intval', $stickies);
        }

        return in_array($id, $this->stickies, true);
    }

    public function get(int $id): string
    {
        return $this->is_sticky($id)
            ? 'yes'
            : 'no';
    }

    public function update(int $id, $data): bool
    {
        if ('yes' === $data) {
            stick_post($id);
        } else {
            unstick_post($id);
        }

        wp_update_post(['ID' => $id]);

        return true;
    }

}