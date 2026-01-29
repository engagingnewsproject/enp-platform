<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;
use WC_Order_Item_Fee;

class Fees extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $values = [];

        foreach ($order->get_items(['fee']) as $item) {
            if ($item instanceof WC_Order_Item_Fee) {
                $values[] = sprintf('%s - %s', wc_price($item->get_amount()), $item->get_name());
            }
        }

        if (empty($values)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(implode(',', $values));
    }

}