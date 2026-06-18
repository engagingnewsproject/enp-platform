<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter;

use AC;
use AC\Type\Value;
use AC\Type\ValueCollection;

class Count implements AC\CollectionFormatter
{

    public function format(ValueCollection $collection): Value
    {
        $count = count($collection);
        $count_value = $count . ' ' . _n('Event', 'Events', $count, 'the-events-calendar');

        return new Value($collection->get_id(), $count_value);
    }

}