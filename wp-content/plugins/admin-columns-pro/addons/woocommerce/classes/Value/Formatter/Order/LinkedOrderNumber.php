<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Type\Value;
use WC_Order;

class LinkedOrderNumber extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $order_number = sprintf('#%s', $order->get_order_number());
        $edit_link = $order->get_edit_order_url();

        if ($edit_link) {
            $order_number = sprintf('<a href="%s">%s</a>', $edit_link, $order_number);
        }

        return $value->with_value($order_number);
    }

}