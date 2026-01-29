<?php

namespace ACP\Formatter\NetworkSite;

use AC\Formatter;
use AC\Type\Value;

class SiteProperty implements Formatter
{

    private $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function format(Value $value): Value
    {
        $property = get_site($value->get_id())->{$this->property} ?? null;

        return $property
            ? $value->with_value($property)
            : new Value(null);
    }
}