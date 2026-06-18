<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class Refunds extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $refunded = $order->get_total_refunded();

        if ( ! $refunded) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            wc_price($refunded, ['currency' => $order->get_currency()])
        );
    }

}