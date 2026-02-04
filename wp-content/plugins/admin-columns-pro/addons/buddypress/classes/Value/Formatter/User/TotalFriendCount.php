<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\User;

use AC\Formatter;
use AC\Type\Value;

class TotalFriendCount implements Formatter
{

    public function format(Value $value): Value
    {
        return $value->with_value(
            bp_get_total_friend_count($value->get_id())
        );
    }

}