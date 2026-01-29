<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Helper;

class TotalSales implements Formatter
{

    public function format(Value $value)
    {
        $values = [];

        foreach ((new Helper\User())->get_shop_order_totals_for_user($value->get_id()) as $total) {
            if ($total) {
                $values[] = wc_price($total);
            }
        }

        if (empty($values)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(implode(' | ', $values));
    }

}