<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use WC_Order_Item_Product;

class Products implements Formatter
{

    public function format($value, $id = null)
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $product_ids = new ValueCollection($value->get_id(), []);

        foreach ($order->get_items() as $item) {
            if ($item instanceof WC_Order_Item_Product && $item->get_quantity() > 0) {
                $product_ids->add(new Value($item->get_variation_id() ?: $item->get_product_id()));
            }
        }

        if (count($product_ids) === 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $product_ids;
    }

}