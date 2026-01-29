<?php

declare(strict_types=1);

namespace ACA\GravityForms\Query;

use AC;
use ACA\GravityForms\TableScreen;
use ACP\Query;

class EntryFactory implements Query\QueryFactory
{

    public function can_create(AC\TableScreen $table_screen): bool
    {
        return $table_screen instanceof TableScreen\Entry;
    }

    public function create(array $bindings): Query
    {
        return new Entry($bindings);
    }

}