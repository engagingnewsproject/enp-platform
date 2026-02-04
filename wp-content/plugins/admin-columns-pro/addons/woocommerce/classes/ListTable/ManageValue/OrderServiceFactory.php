<?php

declare(strict_types=1);

namespace ACA\WC\ListTable\ManageValue;

use AC;
use AC\TableScreen\ManageValueService;
use AC\TableScreen\ManageValueServiceFactory;
use ACA\WC\TableScreen;

class OrderServiceFactory implements ManageValueServiceFactory
{

    public function can_create(AC\TableScreen $table_screen): bool
    {
        return $table_screen instanceof TableScreen\Order;
    }

    public function create(
        AC\TableScreen $table_screen,
        AC\Table\ManageValue\RenderFactory $factory,
        int $priority = 100
    ): ManageValueService {
        return new Order('shop_order', $factory);
    }

}