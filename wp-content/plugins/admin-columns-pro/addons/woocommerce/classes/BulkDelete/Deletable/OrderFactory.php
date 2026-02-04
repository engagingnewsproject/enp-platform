<?php

declare(strict_types=1);

namespace ACA\WC\BulkDelete\Deletable;

use AC\TableScreen;
use ACA\WC;
use ACP\Editing\BulkDelete\Deletable;
use ACP\Editing\BulkDelete\StrategyFactory;

class OrderFactory implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Deletable
    {
        if ( ! $table_screen instanceof WC\TableScreen\Order) {
            return null;
        }

        return new Order();
    }

}