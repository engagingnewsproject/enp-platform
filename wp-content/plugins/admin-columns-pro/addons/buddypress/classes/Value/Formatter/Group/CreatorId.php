<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\Group;

use AC\Formatter;
use AC\Type\Value;

class CreatorId implements Formatter
{

    public function format(Value $value): Value
    {
        return new Value(groups_get_group($value->get_id())->creator_id);
    }

}