<?php

namespace ACP\Export\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Shortcodes implements Formatter
{

    public function format(Value $value)
    {
        $shortcodes = ac_helper()->string->get_shortcodes((string)$value);

        if ( ! $shortcodes) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            implode(', ', array_keys($shortcodes))
        );
    }

}