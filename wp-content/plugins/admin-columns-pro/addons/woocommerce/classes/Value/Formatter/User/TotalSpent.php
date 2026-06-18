<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class TotalSpent implements Formatter
{

    public function format(Value $value)
    {
        $spent = wc_get_customer_total_spent($value->get_id());

        if (in_array($spent, ['0.00', '0,00'], true)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($spent);
    }

}