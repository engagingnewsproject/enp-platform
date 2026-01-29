<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\TableScreen;

use AC;
use AC\TableScreen;
use AC\TableScreen\TableRows;
use ACA\WC\TableScreen\TableRows\Order;

class TableRowsFactory implements AC\TableScreen\TableRowsFactory
{

    public function create(TableScreen $table_screen): ?TableRows
    {
        if ($table_screen instanceof OrderSubscription) {
            return new Order($table_screen);
        }

        return null;
    }

}