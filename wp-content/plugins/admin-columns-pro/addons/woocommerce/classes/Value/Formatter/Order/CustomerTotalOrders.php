<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class CustomerTotalOrders implements Formatter
{

    public function format(Value $value)
    {
        $order_id = $value->get_id();
        $order = wc_get_order($order_id);
        $customer_id = $order->get_customer_id();

        if ( ! $customer_id) {
            throw new ValueNotFoundException("Customer ID not found for order ID {$order_id}");
        }

        $total_orders = wc_get_customer_order_count($customer_id);

        return $value->with_value((string)$total_orders);
    }
}