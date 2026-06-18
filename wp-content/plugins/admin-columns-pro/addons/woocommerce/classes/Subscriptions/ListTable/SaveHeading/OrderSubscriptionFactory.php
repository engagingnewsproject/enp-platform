<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ListTable\SaveHeading;

use AC\Table\SaveHeading\ScreenColumnsFactory;
use AC\TableScreen;
use ACA\WC\Subscriptions\TableScreen\OrderSubscription;

class OrderSubscriptionFactory extends ScreenColumnsFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof OrderSubscription;
    }

}