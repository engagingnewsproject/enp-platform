<?php

declare(strict_types=1);

namespace ACA\BP\TableScreen;

use AC;
use AC\TableScreen;
use AC\TableScreen\TableRows;
use ACA\BP\TableScreen\TableRows\Groups;

class TableRowsFactory implements AC\TableScreen\TableRowsFactory
{

    public function create(TableScreen $table_screen): ?TableRows
    {
        if ($table_screen instanceof Group) {
            return new Groups($table_screen);
        }

        return null;
    }

}