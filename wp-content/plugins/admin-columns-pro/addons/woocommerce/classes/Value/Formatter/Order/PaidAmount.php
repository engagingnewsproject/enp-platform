<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class PaidAmount extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        if ( ! $order->is_paid()) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $paid = $order->get_total() - $order->get_total_refunded();

        if ( ! $paid) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            wc_price($paid, ['currency' => $order->get_currency()])
        );
    }

}