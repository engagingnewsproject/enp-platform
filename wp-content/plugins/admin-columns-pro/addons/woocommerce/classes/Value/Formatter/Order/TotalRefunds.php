<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Type\Value;
use WC_Order;

class TotalRefunds extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        return $value->with_value($order->get_total_refunded());
    }

}