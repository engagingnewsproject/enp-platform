<?php

declare(strict_types=1);

namespace ACA\Pods\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class IdCollection implements Formatter
{

    private $id_property;

    public function __construct(string $id_property = 'ID')
    {
        $this->id_property = $id_property;
    }

    public function format(Value $value): ValueCollection
    {
        $raw_value = $value->get_value();

        if ( ! is_array($raw_value)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if (isset($raw_value[$this->id_property])) {
            return new ValueCollection($value->get_id(), [new Value($raw_value[$this->id_property])]);
        }

        // Multiple records
        $collection = new ValueCollection($value->get_id(), []);

        foreach ($raw_value as $file) {
            if (isset($file[$this->id_property])) {
                $collection->add(new Value($file[$this->id_property]));
            }
        }

        return $collection;
    }

}