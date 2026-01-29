<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;

class Fallback implements Formatter
{

    public function format(Value $value)
    {
        if ($value->get_value()) {
            return $value;
        }

        return $value->with_value('');
    }

}