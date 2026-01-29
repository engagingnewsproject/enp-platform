<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Type\Value;

class PostVisibility implements AC\Formatter
{

    public function format(Value $value)
    {
        $post = get_post($value->get_id());

        if ( ! $post) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $states = get_post_states($post);

        if (isset($states['protected'])) {
            return $value->with_value($states['protected']);
        }

        if (isset($states['private'])) {
            return $value->with_value($states['private']);
        }

        return $value->with_value(__('Public'));
    }

}