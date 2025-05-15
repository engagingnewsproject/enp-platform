<?php

namespace ACA\WC\Settings\ShopOrder;

use AC;

class CustomerLink extends AC\Settings\Column\UserLink
{

    public function format($value, $order_id)
    {
        $order = wc_get_order($order_id);

        return $order
            ? parent::format($value, $order->get_user_id())
            : null;
    }
}