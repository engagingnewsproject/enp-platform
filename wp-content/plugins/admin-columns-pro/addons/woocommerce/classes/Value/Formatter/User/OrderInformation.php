<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Order;

class OrderInformation implements Formatter
{

    public function format(Value $value)
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            sprintf(
                '<span class="order order-%s" %s>%s</span>',
                esc_attr($order->get_status()),
                ac_helper()->html->get_tooltip_attr($this->get_order_tooltip($order)),
                ac_helper()->html->link($order->get_edit_order_url(), $order->get_order_number())
            )
        );
    }

    private function get_order_tooltip(WC_Order $order): string
    {
        $tooltip = [
            wc_get_order_status_name($order->get_status()),
        ];

        $item_count = $order->get_item_count();

        if ($item_count) {
            $tooltip[] = $item_count . ' ' . __('items', 'codepress-admin-columns');
        }

        $total = $order->get_total();

        if ($total) {
            $tooltip[] = get_woocommerce_currency_symbol($order->get_currency()) . wc_trim_zeros(
                    number_format((float)$total, 2)
                );
        }

        $date = $order->get_date_created();
        if ($date) {
            $tooltip[] = ac_format_date(get_option('date_format'), $date->getTimestamp());
        }

        return implode(' | ', $tooltip);
    }

}