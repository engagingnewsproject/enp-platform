<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Formatter;
use AC\Type\Value;

class WcPrice implements Formatter
{

    public function format(Value $value): Value
    {
        return $value->with_value(
            wc_price((string)$value)
        );
    }

}