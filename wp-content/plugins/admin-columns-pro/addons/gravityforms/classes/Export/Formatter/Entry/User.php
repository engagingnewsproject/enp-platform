<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Formatter\Entry;

use AC\Formatter;
use AC\Type\Value;
use WP_User;

class User implements Formatter
{

    public function format(Value $value)
    {
        $user = get_userdata($value->get_id());

        if ( ! $user instanceof WP_User) {
            return '';
        }

        return $value->with_value(
            ac_helper()->user->get_formatted_name($user)
        );
    }

}