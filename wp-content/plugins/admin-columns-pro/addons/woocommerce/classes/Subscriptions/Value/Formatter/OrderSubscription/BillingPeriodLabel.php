<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\OrderSubscription;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class BillingPeriodLabel implements Formatter
{

    public function format(Value $value)
    {
        $periods = wcs_get_available_time_periods();

        if ( ! array_key_exists($value->get_value(), $periods)) {
            throw ValueNotFoundException::from_id($value->get_value());
        }

        return $value->with_value($periods[$value->get_value()]);
    }

}