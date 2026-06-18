<?php

declare(strict_types=1);

namespace ACA\WC\Editing\Strategy;

use AC;
use ACA\WC\Subscriptions\TableScreen\OrderSubscription;
use ACA\WC\TableScreen;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;

class OrderFactory implements StrategyFactory
{

    public function create(AC\TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof TableScreen\Order && ! $table_screen instanceof OrderSubscription) {
            return null;
        }

        return new Order();
    }

}