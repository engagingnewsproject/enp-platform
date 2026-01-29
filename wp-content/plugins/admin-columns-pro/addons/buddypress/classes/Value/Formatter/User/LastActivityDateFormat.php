<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\User;

use AC\Formatter;
use AC\Type\Value;

class LastActivityDateFormat implements Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(bp_core_get_last_activity($value->get_value()));
    }

}