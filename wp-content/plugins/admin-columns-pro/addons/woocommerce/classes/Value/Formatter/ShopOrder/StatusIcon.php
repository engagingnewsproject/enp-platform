<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\ShopOrder;

use AC\Formatter;
use AC\Type\Value;

class StatusIcon implements Formatter
{

    public function format(Value $value)
    {
        $status = wc_get_order($value->get_id())->get_status();
        $label = $this->get_status_label($status);

        $icon = sprintf(
            '<mark %s class="%s" style="display: none;">%s</mark>',
            ac_helper()->html->get_tooltip_attr($label),
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