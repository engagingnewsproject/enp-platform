<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\UserSubscription;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class SubscriptionIdCollection implements Formatter
{

    public function format(Value $value)
    {
        $subscription_ids = wcs_get_users_subscription_ids($value->get_id());

        if (empty($subscription_ids)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return ValueCollection::from_ids($value->get_id(), $subscription_ids);
    }

}