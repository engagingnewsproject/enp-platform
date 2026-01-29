<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Formatter;
use AC\Type\Value;

class IsCustomerIcon implements Formatter
{

    public function format(Value $value)
    {
        $icon = $value->get_value()
            ? ac_helper()->icon->yes(get_userdata($value->get_id())->display_name)
            : ac_helper()->icon->no(__('Guest', 'woocommerce'));

        return $value->with_value($icon);
    }

}