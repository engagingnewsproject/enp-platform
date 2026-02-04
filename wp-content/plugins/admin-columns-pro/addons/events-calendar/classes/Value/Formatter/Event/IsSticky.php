<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Event;

use AC;
use AC\Type\Value;

class IsSticky implements AC\Formatter
{

    public function format(Value $value): Value
    {
        return $value->with_value(-1 === get_post_field('menu_order', $value->get_id()));
    }
}