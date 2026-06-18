<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\Group;

use AC\Formatter;
use AC\Type\Value;

class Avatar implements Formatter
{

    public function format(Value $value): Value
    {
        return $value->with_value(bp_core_fetch_avatar([
            'item_id' => $value->get_id(),
            'object'  => 'group',
        ]));
    }

}