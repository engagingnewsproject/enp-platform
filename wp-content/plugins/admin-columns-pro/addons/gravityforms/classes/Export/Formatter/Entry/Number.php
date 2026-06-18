<?php

namespace ACA\GravityForms\Export\Formatter\Entry;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Number implements Formatter
{

    public function format(Value $value)
    {
        $number = $value->get_value();

        if ( ! is_numeric($number)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($number);
    }

}