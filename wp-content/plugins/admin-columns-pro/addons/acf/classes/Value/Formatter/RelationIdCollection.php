<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class RelationIdCollection implements Formatter
{

    public function format(Value $value): ValueCollection
    {
        $raw_value = $value->get_value();

        if ( ! $raw_value) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $ids = (array)$raw_value;

        return ValueCollection::from_ids($value->get_id(), $ids);
    }

}