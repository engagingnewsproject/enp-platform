<?php

namespace ACA\WC\Export\Order;

use ACP;

class ProductsSold implements ACP\Export\Service
{

    public function get_value($id)
    {
        $order = wc_get_order($id);

        return $order ? $order->get_item_count() : '';
    }

}