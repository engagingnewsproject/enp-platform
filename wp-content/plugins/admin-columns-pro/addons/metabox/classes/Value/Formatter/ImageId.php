<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;

class ImageId implements AC\Formatter
{

    public function format(Value $value): Value
    {
        $image = $value->get_value();

        if (empty($image)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if (is_numeric($image)) {
            return new Value((int)$image);
        }

        if ( ! isset($image['ID'])) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return new Value((int)$image['ID']);
    }

}