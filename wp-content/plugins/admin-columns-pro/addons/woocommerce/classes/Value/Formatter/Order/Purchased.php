<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Value\Extended\ExtendedValue;

class Purchased implements Formatter
{

    private ExtendedValue $extended_value;

    public function __construct(ExtendedValue $extended_value)
    {
        $this->extended_value = $extended_value;
    }

    public function format($value, $id = null)
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $count = $order->get_item_count();

        if ( ! $count) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $count_label = sprintf(_n('%d product', '%d products', $count, 'codepress-admin-columns'), $count);

        return $value->with_value(
            $this->extended_value->get_link((int)$value->get_id(), $count_label)->render()
        );
    }

}