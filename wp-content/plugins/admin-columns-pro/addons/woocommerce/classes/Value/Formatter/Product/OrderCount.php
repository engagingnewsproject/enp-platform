<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class OrderCount implements Formatter
{

    public function format(Value $value)
    {
        $count = $this->get_count($value->get_id());

        if ( ! $count) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($count);
    }

    private function get_count(int $post_id)
    {
        global $wpdb;

        $num_orders = $wpdb->get_var(
            $wpdb->prepare(
                "
			    SELECT COUNT( * )
			    FROM {$wpdb->prefix}wc_orders wc_o
			    JOIN {$wpdb->prefix}wc_order_product_lookup wc_opl ON wc_o.ID = wc_opl.order_id AND wc_opl.product_id = %d
			    ",
                $post_id
            )
        );

        return $num_orders;
    }

}