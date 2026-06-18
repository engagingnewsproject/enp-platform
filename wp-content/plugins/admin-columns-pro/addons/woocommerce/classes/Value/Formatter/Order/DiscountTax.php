<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class DiscountTax extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $tax = $order->get_discount_tax();

        if ( ! $tax) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(wc_price($tax));
    }

}