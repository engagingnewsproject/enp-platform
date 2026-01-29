<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use WC_Order;

class OrderMeta extends OrderMethod
{

    private $meta_key;

    public function __construct(string $meta_key)
    {
        $this->meta_key = $meta_key;
    }

    protected function get_order_value(WC_Order $order, Value $value): Value
    {
        $meta = $order->get_meta($this->meta_key);

        if (null === $meta) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($meta);
    }

}