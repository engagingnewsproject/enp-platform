<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\User;

use AC\Formatter;
use AC\Type\Value;

class UserStatus implements Formatter
{

    public function format(Value $value): Value
    {
        $user = get_userdata($value->get_id());

        $user_status = $user->user_status ?? null;

        return $value->with_value(
            absint($user_status) === 1 ? __('Spammer', 'buddypress') : __('Active', 'buddypress')
        );
    }

}