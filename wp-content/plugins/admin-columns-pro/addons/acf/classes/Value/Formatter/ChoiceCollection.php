<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class ChoiceCollection implements Formatter
{

    public function format(Value $value): ValueCollection
    {
        $raw_values = $value->get_value();

        if ( ! $raw_values) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $collection = new ValueCollection($value->get_id());

        foreach ((array)$raw_values as $raw_value) {
            $collection->add(new Value($value->get_id(), $raw_value));
        }

        return $collection;
    }

}