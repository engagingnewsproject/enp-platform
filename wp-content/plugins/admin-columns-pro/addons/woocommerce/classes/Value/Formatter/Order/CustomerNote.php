<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class CustomerNote implements Formatter
{

    public function format(Value $value)
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $customer_note = $order->get_customer_note();

        if ( ! $customer_note) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($customer_note);
    }

}