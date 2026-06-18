<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class FieldsetValues implements AC\Formatter
{

    public function format(Value $value)
    {
        $fieldset = $value->get_value();

        if ( ! is_array($fieldset)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $values = [];

        foreach ($fieldset as $key => $fieldset_value) {
            $values[] = sprintf('<strong>%s:</strong> %s', $key, $fieldset_value);
        }

        return $value->with_value(implode('<br>', $values));
    }

}