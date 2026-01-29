<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class GalleryIds implements Formatter
{

    public function format(Value $value): ValueCollection
    {
        $raw = explode(',', $value->get_value());
        $raw = array_filter($raw);

        if (empty($raw)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $raw);
    }

}