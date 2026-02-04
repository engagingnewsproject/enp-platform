<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;

class SkypeLink implements Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(
            ac_helper()->html->link('skype:' . $value->get_value(), $value->get_value())
        );
    }
}