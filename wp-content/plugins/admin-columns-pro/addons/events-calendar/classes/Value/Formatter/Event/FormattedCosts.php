<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Event;

use AC;
use AC\Type\Value;

class FormattedCosts implements AC\Formatter
{

    public function format(Value $value): Value
    {
        return $value->with_value(tribe_get_formatted_cost($value->get_id()));
    }
}