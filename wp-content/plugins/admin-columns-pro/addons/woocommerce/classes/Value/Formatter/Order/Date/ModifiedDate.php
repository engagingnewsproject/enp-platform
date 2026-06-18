<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order\Date;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use ACA\WC\Value\Formatter\Order\OrderMethod;
use WC_DateTime;
use WC_Order;

class ModifiedDate extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $date = $order->get_date_modified();

        if ( ! $date instanceof WC_DateTime) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($date->format('Y-m-d H:i:s'));
    }

}