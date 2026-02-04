<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Formatter\Entry;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use GFAPI;

class EntryProperty implements Formatter
{

    private string $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function format(Value $value)
    {
        $field = GFAPI::get_entry($value->get_id())[$this->property] ?? null;

        if (null === $field) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if ('created_by' === $this->property) {
            return new Value((int)$field);
        }

        return $value->with_value($field);
    }

}