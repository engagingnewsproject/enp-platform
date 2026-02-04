<?php

declare(strict_types=1);

namespace ACA\WC\ListTable\SaveHeading;

use AC\Table\SaveHeading\ScreenColumnsFactory;
use AC\TableScreen;
use ACA\WC\TableScreen\Order;

class OrderFactory extends ScreenColumnsFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof Order;
    }

}