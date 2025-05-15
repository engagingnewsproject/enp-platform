<?php

namespace ACA\WC\Export\Order;

use ACP;

class Date implements ACP\Export\Service
{

    public function get_value($id)
    {
        $order = wc_get_order($id);

        if ( ! $order) {
            return '';
        }

        $order_timestamp = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : '';

        if ( ! $order_timestamp) {
            return '';
        }

        return $order->get_date_created()->date_i18n(
            apply_filters('woocommerce_admin_order_date_format', __('M j, Y', 'woocommerce'))
        );
    }

}