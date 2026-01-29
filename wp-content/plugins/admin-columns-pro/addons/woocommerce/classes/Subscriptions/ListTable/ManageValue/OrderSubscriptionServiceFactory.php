<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ListTable\ManageValue;

use AC;
use AC\TableScreen\ManageValueService;
use AC\TableScreen\ManageValueServiceFactory;
use ACA\WC\ListTable\ManageValue\Order;
use ACA\WC\Subscriptions\TableScreen\OrderSubscription;

class OrderSubscriptionServiceFactory implements ManageValueServiceFactory
{

    public function can_create(AC\TableScreen $table_screen): bool
    {
        return $table_screen instanceof OrderSubscription;
    }

    public function create(
        AC\TableScreen $table_screen,
        AC\Table\ManageValue\RenderFactory $factory,
        int $priority = 100
    ): ManageValueService {
        return new Order('shop_subscription', $factory);
    }

}