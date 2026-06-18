<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter\EventSeries;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use TEC;

/**
 * Export Model for AllDayEvent column
 */
class Events implements Formatter
{

    public function format(Value $value)
    {
        if ( ! class_exists(TEC\Events_Pro\Custom_Tables\V1\Repository\Events::class) || ! function_exists('tribe')) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            tribe(TEC\Events_Pro\Custom_Tables\V1\Repository\Events::class)
                ->get_occurrence_count_for_series($value->get_id())
        );
    }

}