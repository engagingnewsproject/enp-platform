<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class TotalDiscount extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $discount = $order->get_total_discount();

        if ( ! $discount) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($order->get_total_discount());
    }

}