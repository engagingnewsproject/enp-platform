<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class LastOrder implements Formatter
{

    public function format(Value $value): Value
    {
        $orders = wc_get_orders([
            'limit'       => 1,
            'status'      => 'wc-completed',
            'customer_id' => $value->get_id(),
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);

        if (empty($orders)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $first_order = $orders[0];

        return new Value($first_order->get_id());
    }

}