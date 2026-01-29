<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Formatter\Entry;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\GravityForms\Field\Field;

class Address implements Formatter
{

    private Field $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    public function format(Value $value)
    {
        $address = $this->field->get_entry_value((int)$value->get_id());

        if ( ! $address) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            strip_tags(str_replace('<br />', '; ', $address))
        );
    }

}