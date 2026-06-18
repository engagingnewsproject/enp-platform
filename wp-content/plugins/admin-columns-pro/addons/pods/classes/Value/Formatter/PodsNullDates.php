<?php

declare(strict_types=1);

namespace ACA\Pods\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class PodsNullDates implements Formatter
{

    public function format(Value $value): Value
    {
        if ($value->get_value() === '0000-00-00') {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if ($value->get_value() === '0000-00-00 00:00:00') {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value;
    }

}