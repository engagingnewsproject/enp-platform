<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class ShippingTax extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $shipping_tax = $order->get_shipping_tax();

        if ( ! $shipping_tax) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            wc_price($shipping_tax, ['currency' => $order->get_currency()])
        );
    }

}