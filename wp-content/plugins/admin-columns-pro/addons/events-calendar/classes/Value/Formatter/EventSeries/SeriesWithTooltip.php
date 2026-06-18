<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\EventSeries;

use AC;
use AC\Type\Value;
use ACA\EC\Value\ExtendedValue\EventSeries;

class SeriesWithTooltip implements AC\Formatter
{

    private EventSeries $extended_value;

    public function __construct(EventSeries $extended_value)
    {
        $this->extended_value = $extended_value;
    }

    public function format(Value $value): Value
    {
        $events = tribe_get_events([
            'posts_per_page' => -1,
            'series'         => $value->get_id(),
            'fields'         => 'ids',
        ]);

        $title = sprintf(_n('%d event', '%d events', count($events), 'tribe-events-calendar-pro'), count($events));

        $link = $this->extended_value->get_link(
            $value->get_id(),
            $title
        )->with_title(get_the_title($value->get_id()));

        return $value->with_value(
            $link->with_params([
                'series_id' => $value->get_id(),
            ])->render()
        );
    }

}

