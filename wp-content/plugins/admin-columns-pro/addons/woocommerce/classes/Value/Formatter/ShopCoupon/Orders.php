<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopCoupon;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Orders implements Formatter
{

    public function format(Value $value)
    {
        $orders = $this->get_order_ids_by_coupon_id($value->get_id());

        if (empty($orders)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $number_of_orders = count($orders);

        return $value->with_value(
            sprintf(_n('%s item', '%s items', $number_of_orders), $number_of_orders)
        );
    }

    private function get_order_ids_by_coupon_id($id): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wc_order_coupon_lookup';

        $sql = "
			SELECT DISTINCT(order_id)
			FROM {$table}
			WHERE coupon_id = %d
		";

        return $wpdb->get_col($wpdb->prepare($sql, $id));
    }

}