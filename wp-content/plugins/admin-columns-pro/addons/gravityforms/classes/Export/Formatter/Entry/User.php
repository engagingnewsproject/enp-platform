<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Formatter\Entry;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Helper;
use AC\Type\Value;
use WP_User;

class User implements Formatter
{

    public function format(Value $value)
    {
        $user = get_userdata($value->get_id());

        if ( ! $user instanceof WP_User) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            Helper\User::create()->get_formatted_name($user)
        );
    }

}