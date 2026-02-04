<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class CustomerSince implements Formatter
{

    public function format(Value $value)
    {
        $orders = wc_get_orders([
            'fields'      => 'id',
            'customer_id' => $value->get_id(),
            'limit'       => -1,
            'return'      => 'ids',
        ]);

        if (empty($orders)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(end($orders));
    }

}