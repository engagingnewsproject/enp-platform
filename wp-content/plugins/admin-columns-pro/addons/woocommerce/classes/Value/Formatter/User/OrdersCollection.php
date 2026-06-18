<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class OrdersCollection implements Formatter
{

    private array $order_status;

    public function __construct(array $order_status = [])
    {
        $this->order_status = $order_status;
    }

    public function format(Value $value): ValueCollection
    {
        $args = [
            'customer_id' => $value->get_id(),
            'limit'       => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'return'      => 'ids',
        ];

        if ( ! empty($this->order_status)) {
            $args['status'] = $this->order_status;
        }

        $orders = wc_get_orders($args);

        if (empty($orders)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $orders);
    }

}