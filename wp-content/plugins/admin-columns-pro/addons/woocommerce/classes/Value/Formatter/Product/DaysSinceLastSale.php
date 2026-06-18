<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Product;

class DaysSinceLastSale extends ProductMethod
{

    protected function get_product_value(WC_Product $product, Value $value): Value
    {
        global $wpdb;

        $product_id = $product->get_id();

        $last_order_date = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT MAX(o.date_created_gmt)
                FROM {$wpdb->prefix}wc_orders AS o
                INNER JOIN {$wpdb->prefix}wc_order_product_lookup AS opl
                    ON o.id = opl.order_id
                WHERE o.type = 'shop_order'
                    AND o.status = 'wc-completed'
                    AND (opl.product_id = %d OR opl.variation_id = %d)
                ",
                $product_id,
                $product_id
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
