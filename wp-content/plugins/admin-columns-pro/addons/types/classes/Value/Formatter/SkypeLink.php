<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Formatter;
use AC\Helper;
use AC\Type\Value;

class SkypeLink implements Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(
            Helper\Html::create()->link('skype:' . $value->get_value(), $value->get_value())
        );
    }
}