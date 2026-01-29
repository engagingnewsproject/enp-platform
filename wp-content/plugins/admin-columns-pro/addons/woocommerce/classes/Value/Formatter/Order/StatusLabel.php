<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Type\Value;
use WC_Order;

class StatusLabel extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $status = $order->get_status();
        $wc_status = 'wc-' . $status;
        $wc_stati = wc_get_order_statuses();

        if (isset($wc_stati[$wc_status])) {
            return $value->with_value($wc_stati[$wc_status]);
        }

        $stati = get_post_stati(['internal' => 0], 'objects');

        $label = isset($stati[$status])
            ? $stati[$status]->label
            : $status;

        return $value->with_value($label);
    }

}