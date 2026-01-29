<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\Order;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class RelatedSubscription implements Formatter
{

    public function format(Value $value)
    {
        $subscriptions = wcs_get_subscriptions_for_order($value->get_id(), ['order_type' => 'any']);

        if (empty($subscriptions)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $ids = wp_list_pluck($subscriptions, 'id');

        return ValueCollection::from_ids($value->get_id(), $ids);
    }

}