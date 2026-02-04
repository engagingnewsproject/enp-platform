<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

trait PostTypeTrait
{

    public function get_post_types(): array
    {
        $post_type = $this->settings['post_type'] ?? null;

        if ( ! $post_type || in_array($post_type, ['all', 'any'])) {
            return [];
        }

        if (is_string($post_type)) {
            return [$post_type];
        }

        if ( ! is_array($post_type)) {
            return [];
        }

        return $post_type;
    }

}