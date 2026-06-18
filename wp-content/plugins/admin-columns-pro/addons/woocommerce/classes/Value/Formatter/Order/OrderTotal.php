<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Type\Value;
use WC_Order;

class OrderTotal extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        return $value->with_value($order->get_formatted_order_total());
    }

}