<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Venue;

use AC;
use AC\Type\Value;

class UpcomingEvent implements AC\Formatter
{

    public function format(Value $value): AC\Type\ValueCollection
    {
        $events = $this->get_events($value);

        if (empty($events)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return AC\Type\ValueCollection::from_ids($value->get_id(), $events);
    }

    private function get_events(Value $value)
    {
        return tribe_get_events([
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'start_date'     => date('Y-m-d H:i'),
            'venue'          => $value->get_id(),
        ]);
    }

}