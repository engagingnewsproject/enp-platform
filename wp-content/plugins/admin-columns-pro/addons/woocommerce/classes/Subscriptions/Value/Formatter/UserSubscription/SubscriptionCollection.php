<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\UserSubscription;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class SubscriptionCollection implements Formatter
{

    public function format(Value $value)
    {
        $subscriptions = wcs_get_users_subscriptions($value->get_id());

        if (empty($subscriptions)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $collection = new ValueCollection($value->get_id());

        foreach ($subscriptions as $subscription) {
            $collection->add(new Value($subscription->get_id(), $subscription));
        }

        return $collection;
    }

}