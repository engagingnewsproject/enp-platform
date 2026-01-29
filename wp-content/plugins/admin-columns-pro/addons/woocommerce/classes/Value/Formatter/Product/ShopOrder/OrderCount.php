<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product\ShopOrder;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class OrderCount implements Formatter
{

    public function format(Value $value)
    {
        global $wpdb;

        $num_orders = $wpdb->get_var(
            $wpdb->prepare(
                "
			SELECT COUNT( 1 )
			FROM {$wpdb->prefix}woocommerce_order_items wc_oi
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta wc_oim ON wc_oi.order_item_id = wc_oim.order_item_id
			WHERE wc_oim.meta_key = '_product_id'
			AND wc_oim.meta_value = %d",
                $value->get_id()
            )
        );

        if ( ! $num_orders) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($num_orders);
    }

}