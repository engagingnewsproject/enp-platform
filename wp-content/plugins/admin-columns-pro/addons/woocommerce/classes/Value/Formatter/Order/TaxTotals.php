<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class TaxTotals extends OrderMethod
{

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $taxes = $order->get_tax_totals();

        if (empty($taxes)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $result = [];

        foreach ($taxes as $tax) {
            $result[] = sprintf('<small><strong>%s: </strong></small> %s', $tax->label, $tax->formatted_amount);
        }

        return $value->with_value(
            implode('<br>', $result)
        );
    }

}