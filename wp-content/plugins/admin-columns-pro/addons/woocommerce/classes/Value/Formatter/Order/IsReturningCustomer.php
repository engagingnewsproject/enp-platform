<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Formatter;
use AC\Type\Value;

class IsReturningCustomer implements Formatter
{

    public function format(Value $value)
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT returning_customer FROM {$wpdb->prefix}wc_order_stats WHERE order_id = %d",
            $value->get_id()
        );

        return $value->with_value($wpdb->get_var($sql));
    }

}