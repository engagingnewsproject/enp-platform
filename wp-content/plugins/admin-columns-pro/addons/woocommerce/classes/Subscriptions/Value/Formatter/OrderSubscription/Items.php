<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\OrderSubscription;

use AC\Formatter;
use AC\Type\Value;
use WC_Order_Item_Product;

class Items implements Formatter
{

    public function format(Value $value)
    {
        $order = wcs_get_subscription($value->get_id());
        $items = [];

        foreach ($order->get_items() as $item) {
            if ($item instanceof WC_Order_Item_Product) {
                $items[] = sprintf('%dx %s', $item->get_quantity(), $item->get_name());
            }
        }

        return $value->with_value(implode(',', $items));
    }

}