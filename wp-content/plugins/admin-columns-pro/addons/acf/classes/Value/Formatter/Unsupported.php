<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;

class Unsupported implements Formatter
{

    public function format(Value $value): Value
    {
        $raw = $value->get_value();

        if (is_array($raw)) {
            return $value->with_value(ac_helper()->array->implode_recursive(__(', '), $raw));
        }

        return $value;
    }

}