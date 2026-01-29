<?php

namespace ACP\Formatter\Post;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;

class RevisionCount implements AC\Formatter
{

    public function format(Value $value)
    {
        $count = count(wp_get_post_revisions($value->get_id(), ['posts_per_page' => -1, 'fields' => 'ids']));

        if ($count === 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            count(wp_get_post_revisions($value->get_id(), ['posts_per_page' => -1, 'fields' => 'ids']))
        );
    }

}