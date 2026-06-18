<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product\ShopOrder;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class OrderTotal implements Formatter
{

    public function format(Value $value)
    {
        $total_revenue = $this->get_total_revenue($value->get_id());

        if ( ! $total_revenue) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($total_revenue);
    }

    private function get_total_revenue(int $product_id): ?int
    {
        global $wpdb;

        $num_orders = $wpdb->get_var(
            $wpdb->prepare(
                "
			SELECT
				SUM( wc_oim2.meta_value )
			FROM
				{$wpdb->prefix}woocommerce_order_items wc_oi
			JOIN
				{$wpdb->prefix}woocommerce_order_itemmeta wc_oim
				ON
					wc_oi.order_item_id = wc_oim.order_item_id
			JOIN
				{$wpdb->prefix}woocommerce_order_itemmeta wc_oim2
				ON
					wc_oi.order_item_id = wc_oim2.order_item_id
			WHERE
				wc_oim.meta_key = '_product_id'
				AND
				wc_oim.meta_value = %d
				AND
				wc_oim2.meta_key = '_line_total'
			",
                $product_id
            )
        );

        if ( ! $num_orders) {
            return null;
        }

        return (int)$num_orders;
    }

}