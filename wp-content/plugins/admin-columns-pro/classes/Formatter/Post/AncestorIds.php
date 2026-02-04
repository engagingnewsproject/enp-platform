<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Type\Value;

class AncestorIds implements AC\Formatter
{

    public function format(Value $value)
    {
        $post = get_post($value->get_id());
        if ( ! $post) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $ancestors = $post->ancestors;

        $ancestors = array_reverse($ancestors);

        if ( ! $ancestors) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return AC\Type\ValueCollection::from_ids($value->get_id(), $ancestors);
    }

}