<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\OrderSubscription;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WC_Subscription;

abstract class SubscriptionMethod implements Formatter
{

    abstract protected function get_subscription_value(WC_Subscription $subscription, Value $value): Value;

    public function format(Value $value)
    {
        $order = wcs_get_subscription($value->get_id());

        if ( ! $order) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $this->get_subscription_value($order, $value);
    }

}