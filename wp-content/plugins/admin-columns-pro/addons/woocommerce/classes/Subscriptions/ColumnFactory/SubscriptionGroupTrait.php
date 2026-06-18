<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory;

trait SubscriptionGroupTrait
{

    public function get_group(): string
    {
        return 'woocommerce_subscriptions';
    }
}