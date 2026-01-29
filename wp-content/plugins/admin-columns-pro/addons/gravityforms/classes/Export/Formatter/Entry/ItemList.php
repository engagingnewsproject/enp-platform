<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Formatter\Entry;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class ItemList implements Formatter
{

    public function format(Value $value): Value
    {
        $data = $value->get_value();

        if ( ! $data || ! is_string($data)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $items = unserialize($data, ['allowed_classes' => false]);

        if ( ! is_array($items)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            ac_helper()->array->implode_recursive(', ', $items)
        );
    }

}