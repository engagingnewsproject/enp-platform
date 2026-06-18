<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\Group;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class GroupProperty implements Formatter
{

    private string $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function format(Value $value): Value
    {
        $group = groups_get_group($value->get_id());

        if ( ! $group) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            $group->{$this->property} ?? null
        );
    }

}