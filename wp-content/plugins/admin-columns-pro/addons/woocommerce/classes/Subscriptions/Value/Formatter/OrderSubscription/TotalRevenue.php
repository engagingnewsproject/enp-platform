<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\OrderSubscription;

use AC\Type\Value;
use WC_Order;
use WC_Subscription;

class TotalRevenue extends SubscriptionMethod
{

    protected function get_subscription_value(WC_Subscription $subscription, Value $value): Value
    {
        $total = 0;

        foreach ($subscription->get_related_orders('all') as $order) {
            if ($order instanceof WC_Order) {
                $total += $order->get_total();
            }
        }

        return $value->with_value($total);
    }

}