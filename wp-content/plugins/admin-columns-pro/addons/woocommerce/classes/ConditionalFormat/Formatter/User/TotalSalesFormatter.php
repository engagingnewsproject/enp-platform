<?php

declare(strict_types=1);

namespace ACA\WC\ConditionalFormat\Formatter\User;

use ACP\ConditionalFormat\Formatter;

class TotalSalesFormatter extends Formatter\FloatFormatter
{

    public function get_type(): string
    {
        return self::FLOAT;
    }

    public function format(string $value, $id, string $operator_group): string
    {
        return wc_get_customer_total_spent($id);
    }

}