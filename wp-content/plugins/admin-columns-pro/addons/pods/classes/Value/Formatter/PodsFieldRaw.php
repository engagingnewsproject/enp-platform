<?php

declare(strict_types=1);

namespace ACA\Pods\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;
use ACA\Pods\Field;

class PodsFieldRaw implements Formatter
{

    private Field $field;

    private bool $single;

    public function __construct(Field $field, bool $single = true)
    {
        $this->field = $field;
        $this->single = $single;
    }

    public function format(Value $value)
    {
        return $value->with_value(
            pods_field_raw(
                $this->field->get_pod()->get_name(),
                $value->get_id(),
                $this->field->get_name(),
                $this->single
            )
        );
    }

}