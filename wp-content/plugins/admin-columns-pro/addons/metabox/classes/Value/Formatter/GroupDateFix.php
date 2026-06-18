<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class GroupDateFix implements AC\Formatter
{

    public function format(Value $value)
    {
        $raw_value = $value->get_value();

        if ( ! is_array($raw_value) || ! isset($raw_value['timestamp'])) {
            return $value;
        }

        return $value->with_value((int)$raw_value['timestamp']);
    }

}