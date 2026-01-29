<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Type\Value;
use WC_Order;

class SubTotal extends OrderMethod
{

    private $display_value;

    public function __construct(bool $display_value = true)
    {
        $this->display_value = $display_value;
    }

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        return $value->with_value(
            $this->display_value ? $order->get_subtotal_to_display() : $order->get_subtotal()
        );
    }

}