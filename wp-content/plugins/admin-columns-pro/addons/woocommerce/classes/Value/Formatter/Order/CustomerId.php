<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class CustomerId extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $customer_id = $order->get_customer_id();

        if ( ! $customer_id) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return new Value($order->get_customer_id());
    }

}