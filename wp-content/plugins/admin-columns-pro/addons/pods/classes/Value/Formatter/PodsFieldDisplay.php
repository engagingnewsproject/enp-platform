<?php

declare(strict_types=1);

namespace ACA\Pods\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;
use ACA\Pods\Field;

class PodsFieldDisplay implements Formatter
{

    private Field $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    public function format(Value $value): Value
    {
        return $value->with_value(
            pods_field_display($this->field->get_pod(), $value->get_id(), $this->field->get_name())
        );
    }

}