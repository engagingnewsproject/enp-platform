<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\Event;

use AC;
use AC\Type\Value;

class Duration implements AC\Formatter
{

    public function format(Value $value): Value
    {
        $is_all_day_event = get_post_meta($value->get_id(), '_EventAllDay', true);

        if ($is_all_day_event) {
            $start_date = strtotime(get_post_meta($value->get_id(), '_EventStartDate', true));
            $end_date = strtotime(get_post_meta($value->get_id(), '_EventEndDate', true)) + 1;

            return $value->with_value(human_time_diff($start_date, $end_date));
        }

        $duration_in_seconds = get_post_meta($value->get_id(), '_EventDuration', true);

        if ($duration_in_seconds) {
            return $value->with_value(human_time_diff(0, $duration_in_seconds));
        }

        return $value;
    }
}