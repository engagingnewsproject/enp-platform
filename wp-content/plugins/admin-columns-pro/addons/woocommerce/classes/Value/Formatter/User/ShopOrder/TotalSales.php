<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User\ShopOrder;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Helper;

class TotalSales implements Formatter
{

    private $statuses;

    public function __construct(array $statuses = [])
    {
        $this->statuses = $statuses;
    }

    public function format(Value $value)
    {
        $totals = (new Helper\User())->get_shop_order_totals_for_user($value->get_id(), $this->statuses);

        $values = [];

        foreach ($totals as $total) {
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