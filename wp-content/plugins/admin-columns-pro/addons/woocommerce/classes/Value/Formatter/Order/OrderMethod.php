<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Order;

abstract class OrderMethod implements Formatter
{

    abstract protected function get_order_value(WC_Order $order, Value $value): Value;

    public function format(Value $value): Value
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order instanceof WC_Order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $this->get_order_value($order, $value);
    }

}