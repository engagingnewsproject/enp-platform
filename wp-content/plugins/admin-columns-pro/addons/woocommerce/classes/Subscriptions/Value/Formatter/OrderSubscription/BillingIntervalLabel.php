<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\OrderSubscription;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class BillingIntervalLabel implements Formatter
{

    public function format(Value $value)
    {
        $intervals = wcs_get_subscription_period_interval_strings();

        if ( ! array_key_exists($value->get_value(), $intervals)) {
            throw ValueNotFoundException::from_id($value->get_value());
        }

        return $value->with_value($intervals[$value->get_value()]);
    }

}