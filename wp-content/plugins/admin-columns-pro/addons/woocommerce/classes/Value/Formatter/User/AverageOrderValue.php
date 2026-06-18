<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class AverageOrderValue implements Formatter
{

    public function format(Value $value)
    {
        $user_id = (int)$value->get_id();
        $total_spent = (float)wc_get_customer_total_spent($user_id);
        $order_count = (int)wc_get_customer_order_count($user_id);

        if ($order_count < 1 || $total_spent <= 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(round($total_spent / $order_count, 2));
    }

}
