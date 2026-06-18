<?php

declare(strict_types=1);

namespace ACA\WC\Search\Query;

use AC;
use ACA\WC\TableSCreen;
use ACP\Query;
use ACP\Query\QueryFactory;

class OrderFactory implements QueryFactory
{

    public function can_create(AC\TableScreen $table_screen): bool
    {
        return $table_screen instanceof TableScreen\Order;
    }

    public function create(array $bindings): Query
    {
        return new Order($bindings);
    }

}