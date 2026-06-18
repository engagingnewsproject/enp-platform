<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\Activity;

use AC\Formatter;
use AC\Type\Value;
use BP_Activity_Activity;

class Component implements Formatter
{

    public function format(Value $value): Value
    {
        $activity = new BP_Activity_Activity($value->get_id());

        return $value->with_value($activity->component);
    }

}