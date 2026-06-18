<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Type\Value;

class LatestCommentId implements AC\Formatter
{

    public function format(Value $value)
    {
        $comments = (array)get_comments([
            'number'  => 1,
            'fields'  => 'ids',
            'post_id' => $value->get_id(),
        ]);

        if ( ! $comments) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return new Value((int)$comments[0]);
    }

}