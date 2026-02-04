<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class Repeater implements Formatter
{

    private string $sub_key;

    public function __construct(string $sub_key)
    {
        $this->sub_key = $sub_key;
    }

    public function format(Value $value)
    {
        $repeater_value = $value->get_value();

        if ( ! is_array($repeater_value) || empty($repeater_value)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $values = new ValueCollection($value->get_id());

        foreach ($repeater_value as $row_value) {
            if (array_key_exists($this->sub_key, $row_value)) {
                $values->add(new Value($value->get_id(), $row_value[$this->sub_key]));
            }
        }

        return $values;
    }

}