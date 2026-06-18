<?php

declare(strict_types=1);

namespace ACA\MLA\Export\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class IdParent implements Formatter
{

    public function format(Value $value)
    {
        $post = get_post($value->get_id());

        if ( ! $post) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if ($post->post_parent) {
            return $value->with_value(sprintf('%s → %s', $post->ID, $post->post_parent));
        }

        return $value->with_value($post->ID);
    }

}