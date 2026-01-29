<?php

declare(strict_types=1);

namespace ACP\TableScreen;

use AC\TableScreen;

class TableRowsFactory implements TableScreen\TableRowsFactory
{

    public function create(TableScreen $table_screen): ?TableScreen\TableRows
    {
        switch (true) {
            case $table_screen instanceof Taxonomy:
                return new TableRows\Taxonomy($table_screen);

            case $table_screen instanceof NetworkUser:
                return new TableScreen\TableRows\User($table_screen);
        }

        return null;
    }

}