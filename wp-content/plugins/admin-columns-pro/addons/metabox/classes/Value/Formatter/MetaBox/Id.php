<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter\MetaBox;

use AC;
use AC\Type\Value;

class Id implements AC\Formatter
{

    public function format(Value $value)
    {
        $data = get_post_meta($value->get_id(), 'meta_box', true);

        if (empty($data)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            $data['id'] ?? null
        );
    }

}