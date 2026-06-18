<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class DaysSinceLastOrder implements Formatter
{

    public function format(Value $value)
    {
        global $wpdb;

        $user_id = (int)$value->get_id();

        $last_order_date = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT MAX(date_created_gmt)
                FROM {$wpdb->prefix}wc_orders
                WHERE customer_id = %d
                    AND type = 'shop_order'
                    AND status IN ('wc-completed', 'wc-processing')
                ",
                $user_id
            )
        );

        if ( ! $last_order_date) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $days = (int)round(
            (time() - strtotime($last_order_date)) / DAY_IN_SECONDS
        );

        return $value->with_value(max(0, $days));
    }

}
