<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\OrderSubscription;

use AC\Type\Value;
use WC_Subscription;

class BillingPeriod extends SubscriptionMethod
{

    protected function get_subscription_value(WC_Subscription $subscription, Value $value): Value
    {
        return $value->with_value($subscription->get_billing_period());
    }

}