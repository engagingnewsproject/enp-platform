<?php

declare(strict_types=1);

namespace ACP\Formatter\PostType;

use AC;
use AC\Type\Value;

class SingularLabel implements AC\Formatter
{

    public function format(Value $value)
    {
        $post_type_object = get_post_type_object($value->get_value());

        if ( ! $post_type_object) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($post_type_object->labels->singular_name);
    }

}