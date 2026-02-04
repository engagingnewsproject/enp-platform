<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Event;

use AC;
use AC\Type\Value;

class FieldValue implements AC\Formatter
{

    private string $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function format(Value $value): Value
    {
        $fields = tribe_get_custom_fields($value->get_id());

        if ( ! is_array($fields)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        if ( ! array_key_exists($this->key, $fields)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($fields[$this->key]);
    }
}