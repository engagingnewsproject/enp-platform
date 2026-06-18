<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopOrder;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Helper;
use AC\Type\Value;

class StatusIcon implements Formatter
{

    public function format(Value $value)
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $status = $order->get_status();
        $label = $this->get_status_label($status);

        $icon = sprintf(
            '<mark %s class="%s" style="display: none;">%s</mark>',
            Helper\Html::create()->get_tooltip_attr($label),
            $status,
            $label
        );

        return $value->with_value($icon);
    }

    private function get_status_label($key)
    {
        $key = 'wc-' . $key;
        $statuses = wc_get_order_statuses();

        return $statuses[$key] ?? $key;
    }

}