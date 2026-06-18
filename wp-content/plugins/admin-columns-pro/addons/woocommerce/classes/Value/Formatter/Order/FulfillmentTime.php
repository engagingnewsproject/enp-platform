<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class FulfillmentTime extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $date_created = $order->get_date_created();
        $date_completed = $order->get_date_completed();

        if ( ! $date_created || ! $date_completed) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $diff = $date_completed->getTimestamp() - $date_created->getTimestamp();
        $days = max(0, (int)round($diff / DAY_IN_SECONDS));

        return $value->with_value($days);
    }

}
