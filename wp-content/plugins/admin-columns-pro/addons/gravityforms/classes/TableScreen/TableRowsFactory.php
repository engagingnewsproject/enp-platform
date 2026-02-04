<?php

declare(strict_types=1);

namespace ACA\GravityForms\TableScreen;

use AC;
use AC\TableScreen;

class TableRowsFactory implements AC\TableScreen\TableRowsFactory
{

    public function create(TableScreen $table_screen): ?AC\TableScreen\TableRows
    {
        if ($table_screen instanceof Entry) {
            return new TableRows\Entry($table_screen);
        }

        return null;
    }

}