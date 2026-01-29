<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACA\JetEngine\Field;
use ACA\JetEngine\Utils\FieldOptions;

final class Checkbox implements Formatter
{

    private $options;

    private $field;

    public function __construct(Field\Type\Checkbox $field)
    {
        $this->options = $field->get_options();
        $this->field = $field;
    }

    public function format(Value $value): ValueCollection
    {
        $raw_value = $value->get_value();

        if (empty($raw_value)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $selected = $this->field->value_is_array() ? $raw_value : FieldOptions::get_checked_options($raw_value);
        $values = new ValueCollection($value->get_id());

        foreach ($selected as $option) {
            $values->add(
                new Value(
                    $value->get_id(),
                    $this->options[$option] ?? $option
                )
            );
        }

        return $values;
    }

}