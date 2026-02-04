<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product\Variation;

use AC\CollectionFormatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class Count implements CollectionFormatter
{

    public function format(ValueCollection $collection): Value
    {
        return new Value(
            $collection->get_id(),
            $collection->count()
        );
    }

}