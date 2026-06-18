<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Type\Value;
use WC_Order;

class EditOrderUrl extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $link = sprintf('<a href="%s" target="_blank">%s</a>', $order->get_edit_order_url(), $value->get_value());

        return $value->with_value($link);
    }

}