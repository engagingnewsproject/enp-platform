<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopOrder;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class Products implements Formatter
{

    public function format(Value $value)
    {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
				SELECT om.order_item_id as oid, om.meta_value as product_id, om2.meta_value as variation_id
				FROM {$wpdb->prefix}woocommerce_order_items AS oi
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS om ON ( oi.order_item_id = om.order_item_id )
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS om2 ON ( oi.order_item_id = om2.order_item_id )
				WHERE om.meta_key = '_product_id' 
				AND om2.meta_key ='_variation_id'
				AND oi.order_id = %d
				",
                $value->get_id()
            )
        );

        $product_ids = [];

        foreach ($results as $result) {
            $product_ids[] = $result->variation_id ?: $result->product_id;
        }

        if (empty($product_ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $product_ids);
    }

}