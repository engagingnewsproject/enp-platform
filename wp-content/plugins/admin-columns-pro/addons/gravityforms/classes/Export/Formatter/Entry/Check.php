<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Formatter\Entry;

use AC\Formatter;
use AC\Type\Value;
use ACA\GravityForms\Field\Field;

class Check implements Formatter
{

    private Field $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    public function format(Value $value)
    {
        return $value->with_value(
            $this->field->get_entry_value((int)$value->get_id())
                ? 'checked'
                : ''
        );
    }

}