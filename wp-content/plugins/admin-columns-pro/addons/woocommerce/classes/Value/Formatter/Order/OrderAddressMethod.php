<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use LogicException;

class OrderAddressMethod implements Formatter
{

    private $method;

    public function __construct(string $method)
    {
        $this->method = $method;
    }

    public function format(Value $value)
    {
        $order = wc_get_order($value->get_id());

        if ( ! $order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if ( ! method_exists($order, $this->method)) {
            throw new LogicException(sprintf('Method %s does not exist on order', $this->method));
        }

        return $value->with_value($order->{$this->method}());
    }

}