<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Venue;

use AC;
use AC\Type\Value;

class TribeAddress implements AC\Formatter
{

    public function format(Value $value): Value
    {
        return $value->with_value(tribe_get_address($value->get_id()));
    }

}