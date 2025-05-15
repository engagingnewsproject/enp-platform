<?php

namespace ACA\WC\Export\Order;

use ACP;

class Total implements ACP\Export\Service
{

    public function get_value($id)
    {
        $order = wc_get_order($id);
        if ( ! $order) {
            return '';
        }

        return strip_tags(get_woocommerce_currency_symbol($order->get_currency()) . ' ' . $order->get_total());
    }

}