<?php

declare(strict_types=1);

namespace ACA\WC\TableScreen;

use AC;
use AC\TableScreen;

class TableRowsFactory implements AC\TableScreen\TableRowsFactory
{

    public function create(TableScreen $table_screen): ?AC\TableScreen\TableRows
    {
        if ($table_screen instanceof Order) {
            return new TableRows\Order($table_screen);
        }

        return null;
    }

}