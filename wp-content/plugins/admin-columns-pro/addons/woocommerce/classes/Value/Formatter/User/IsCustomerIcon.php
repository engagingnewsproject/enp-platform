<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Helper;
use AC\Type\Value;

class IsCustomerIcon implements Formatter
{

    public function format(Value $value)
    {
        $user = get_userdata($value->get_id());

        if ( ! $user) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $icon = $value->get_value()
            ? Helper\Icon::create()->yes($user->display_name)
            : Helper\Icon::create()->no(__('Guest', 'woocommerce'));

        return $value->with_value($icon);
    }

}