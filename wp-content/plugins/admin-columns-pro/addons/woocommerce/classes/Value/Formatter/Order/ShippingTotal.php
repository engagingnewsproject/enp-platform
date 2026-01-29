<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class ShippingTotal extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $shipping_total = $order->get_shipping_total();

        if ( ! $shipping_total) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            wc_price($shipping_total, ['currency', $order->get_currency()])
        );
    }

}