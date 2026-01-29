<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use AC\Type\ValueCollection;

class ImageIds implements AC\Formatter
{

    public function format(Value $value)
    {
        $images = $value->get_value();

        if (empty($images)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        // Value can be an array of ID's or Image array
        $image_ids = [];

        foreach ($images as $image_value) {
            if (isset($image_value['ID'])) {
                $image_ids[] = $image_value['ID'];
                continue;
            }

            if (is_numeric($image_value)) {
                $image_ids[] = $image_value;
            }
        }

        return ValueCollection::from_ids($value->get_id(), $image_ids);
    }

}