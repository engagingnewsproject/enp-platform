<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\EventSeries;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WP_Post;

class Title implements Formatter
{

    public function format(Value $value)
    {
        $serie = tec_event_series($value->get_id());

        if ( ! $serie instanceof WP_Post) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            get_the_title($serie->ID)
        );
    }

}