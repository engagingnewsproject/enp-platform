<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class FilterByCustomerLink implements Formatter
{

    public function format(Value $value)
    {
        $user_id = (string)$value;

        if ( ! $user_id) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            ac_helper()->html->link(
                add_query_arg('_customer_user', $value->get_id()),
                $user_id
            )
        );
    }

}