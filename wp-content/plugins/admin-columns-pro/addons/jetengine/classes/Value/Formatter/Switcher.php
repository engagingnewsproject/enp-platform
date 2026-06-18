<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;

class Switcher implements Formatter
{

    public function format(Value $value): Value
    {
        return $value->with_value(
            in_array((string)$value, ['1', 'true', 'on'], true)
        );
    }

}