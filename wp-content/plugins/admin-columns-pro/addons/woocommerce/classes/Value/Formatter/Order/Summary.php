<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Type\Value;
use WC_Order;

class Summary extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $summary = sprintf(
            '<a href="%s"><strong>#%d</strong></a> %s %s<br /><small>%s</small>',
            $order->get_edit_order_url(),
            $order->get_id(),
            $order->get_formatted_billing_full_name(),
            ac_helper()->html->small_block([wc_get_order_status_name($order->get_status())]),
            $order->get_date_created()->format('Y-m-d H:i:s'),
        );

        return $value->with_value($summary);
    }

}