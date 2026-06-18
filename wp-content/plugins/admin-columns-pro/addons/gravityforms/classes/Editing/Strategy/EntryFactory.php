<?php

declare(strict_types=1);

namespace ACA\GravityForms\Editing\Strategy;

use AC\TableScreen;
use ACA\GravityForms;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;

class EntryFactory implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof GravityForms\TableScreen\Entry) {
            return null;
        }

        return new Entry($table_screen->get_list_table());
    }

}