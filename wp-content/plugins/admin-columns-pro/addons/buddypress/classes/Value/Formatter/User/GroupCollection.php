<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\User;

use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class GroupCollection implements Formatter
{

    public function format(Value $value): ValueCollection
    {
        $group_value = new ValueCollection($value->get_id());

        foreach (groups_get_user_groups($value->get_id())['groups'] as $group_id) {
            $group_value->add(
                new Value($group_id)
            );
        }

        return $group_value;
    }

}