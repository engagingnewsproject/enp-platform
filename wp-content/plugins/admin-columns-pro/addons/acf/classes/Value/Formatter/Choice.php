<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class Choice implements Formatter
{

    private array $labels;

    public function __construct(array $labels)
    {
        $this->labels = $labels;
    }

    public function format(Value $value): ValueCollection
    {
        $raw_values = $value->get_value();

        if ( ! $raw_values) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $value_collection = new ValueCollection($value->get_id());

        foreach ((array)$raw_values as $raw_value) {
            if (array_key_exists($raw_value, $this->labels)) {
                $value_collection->add(new Value($value->get_id(), $this->labels[$raw_value]));
            }
        }

        return $value_collection;
    }

}