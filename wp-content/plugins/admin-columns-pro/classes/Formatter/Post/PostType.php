<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Type\Value;

class PostType implements AC\Formatter
{

    public function format(Value $value)
    {
        $post = get_post($value->get_id());

        if ( ! $post) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $post_type_object = get_post_type_object($post->post_type);

        return $value->with_value($post_type_object->label ?? $post->post_type);
    }

}