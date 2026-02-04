<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Event;

use AC;
use AC\Type\Value;

class EventSerieCollection implements AC\Formatter
{

    public function format(Value $value): AC\Type\ValueCollection
    {
        $series = tec_event_series($value->get_id());

        if ( ! $series) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $series_id = is_object($series) && isset($series->ID) ? $series->ID : (int)$series;

        return AC\Type\ValueCollection::from_ids($value->get_id(), [$series_id]);
    }
}
