<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product\ShopOrder;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Customers implements Formatter
{

    public function format(Value $value): Value
    {
        $customers = $this->get_customers($value->get_id());

        if ( ! $customers) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            count($customers)
        );
    }

    public function get_customers(int $id): array
    {
        global $wpdb;

        $post_status = 'wc-completed';

        $sql = "
			SELECT DISTINCT pm.meta_value AS cid
			FROM $wpdb->postmeta AS pm
			INNER JOIN $wpdb->posts AS p
				ON p.ID = pm.post_id AND p.post_status = %s
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS oi
				ON oi.order_id = p.ID AND oi.order_item_type = 'line_item'
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim
				ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_product_id'
			WHERE pm.meta_key = '_customer_user'
			AND oim.meta_value = %d
		";

        return $wpdb->get_col($wpdb->prepare($sql, [$post_status, $id]));
    }

}