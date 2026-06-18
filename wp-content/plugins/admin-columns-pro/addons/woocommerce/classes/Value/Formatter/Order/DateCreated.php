<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class DateCreated extends OrderMethod
{

    private string $date_format;

    public function __construct($date_format = 'Y-m-d H:i:s')
    {
        $this->date_format = $date_format;
    }

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $date_created = $order->get_date_created();

        if ( ! $date_created) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($date_created->format($this->date_format));
    }

}