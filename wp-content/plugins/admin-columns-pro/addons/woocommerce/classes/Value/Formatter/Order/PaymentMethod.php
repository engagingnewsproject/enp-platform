<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Type\Value;
use WC_Order;

class PaymentMethod extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $title = strip_tags($order->get_payment_method_title()) ?: $order->get_payment_method();

        return $value->with_value($title);
    }

}