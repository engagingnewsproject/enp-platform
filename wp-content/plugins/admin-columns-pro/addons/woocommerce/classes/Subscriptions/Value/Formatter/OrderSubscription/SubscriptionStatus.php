<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\OrderSubscription;

use AC\Type\Value;
use WC_Subscription;

class SubscriptionStatus extends SubscriptionMethod
{

    protected function get_subscription_value(WC_Subscription $subscription, Value $value): Value
    {
        $statuses = wcs_get_subscription_statuses();
        $status = 'wc-' . $subscription->get_status();

        $status_label = array_key_exists($status, $statuses)
            ? $statuses[$status]
            : $status;

        return $value->with_value($status_label);
    }

}