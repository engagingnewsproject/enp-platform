<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Query;

use AC;
use ACA\WC\Search\Query\Order;
use ACA\WC\Subscriptions\TableScreen;
use ACP\Query;
use ACP\Query\QueryFactory;

class OrderSubscriptionFactory implements QueryFactory
{

    public function can_create(AC\TableScreen $table_screen): bool
    {
        return $table_screen instanceof TableScreen\OrderSubscription;
    }

    public function create(array $bindings): Query
    {
        return new Order($bindings);
    }

}