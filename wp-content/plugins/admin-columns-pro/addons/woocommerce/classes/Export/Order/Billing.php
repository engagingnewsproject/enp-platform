<?php

namespace ACA\WC\Export\Order;

use ACP;

class Billing implements ACP\Export\Service
{

    public function get_value($id)
    {
        $order = wc_get_order($id);
        if ( ! $order) {
            return '';
        }

        return str_replace('<br/>', ' ', $order->get_formatted_billing_address());
    }

}