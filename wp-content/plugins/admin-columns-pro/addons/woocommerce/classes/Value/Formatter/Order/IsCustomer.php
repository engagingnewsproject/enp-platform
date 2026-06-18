<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Type\Value;
use WC_Order;

class IsCustomer extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $customer_id = $order->get_customer_id();

        return new Value($customer_id, $customer_id !== 0);
    }

}