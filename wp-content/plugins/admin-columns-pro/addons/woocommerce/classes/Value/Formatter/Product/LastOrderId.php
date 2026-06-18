<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class LastOrderId extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        global $wpdb;

        $product_id = $product->get_id();
        $sql = $wpdb->prepare(
            "
            SELECT id
            FROM {$wpdb->prefix}wc_orders as o 
            INNER JOIN {$wpdb->prefix}wc_order_product_lookup opl 
                ON o.id = opl.order_id
            WHERE
                o.type = 'shop_order'
                AND o.status = 'wc-completed'
                AND ( opl.product_id = %d OR opl.variation_id = %d )
            ORDER BY o.date_created_gmt DESC, o.id DESC
            LIMIT 1
        ",
            $product_id,
            $product_id
        );

        $latest_order = $wpdb->get_var($sql);

        if ( ! $latest_order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return new Value($latest_order);
    }

}