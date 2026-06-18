<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class CustomerCount implements Formatter
{

    public function format(Value $value)
    {
        $count = $this->get_customers_by_product((int)$value->get_id());

        if ($count < 1) {
            throw ValueNotFoundException::from_id((int)$value->get_id());
        }

        return $value->with_value(
            $count
        );
    }

    private function get_customers_by_product(int $id): int
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "
            SELECT count(o.customer_id), o.customer_id
            FROM {$wpdb->prefix}wc_orders as o 
            INNER JOIN {$wpdb->prefix}wc_order_product_lookup opl
                ON o.id = opl.order_id AND opl.product_id = %d
            WHERE
                o.type = 'shop_order'
                AND o.status = %s
                AND o.customer_id > 0
            GROUP BY o.customer_id
            ORDER BY o.date_created_gmt DESC
        ",
            $id,
            'wc-completed'
        );

        return count($wpdb->get_col($sql));
    }

}