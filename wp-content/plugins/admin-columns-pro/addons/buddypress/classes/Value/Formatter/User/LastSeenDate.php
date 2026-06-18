<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class LastSeenDate implements Formatter
{

    public function format(Value $value): Value
    {
        $date = bp_get_user_last_activity($value->get_id());

        if ( ! $date) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            strtotime($date)
        );
    }

}