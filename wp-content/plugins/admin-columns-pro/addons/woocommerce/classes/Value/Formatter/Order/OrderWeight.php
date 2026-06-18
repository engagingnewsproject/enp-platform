<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;
use WC_Order_Item_Product;

class OrderWeight extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $total_weight = 0;

        foreach ($order->get_items() as $item) {
            if ( ! $item instanceof WC_Order_Item_Product || ! $item->get_product()) {
                continue;
            }

            $weight = (int)$item->get_quantity() * (float)$item->get_product()->get_weight();
            $total_weight += $weight;
        }

        if ($total_weight === 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            sprintf('%s %s', wc_format_decimal($total_weight), get_option('woocommerce_weight_unit'))
        );
    }

}