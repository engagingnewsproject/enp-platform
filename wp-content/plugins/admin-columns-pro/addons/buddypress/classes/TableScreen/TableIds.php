<?php

declare(strict_types=1);

namespace ACA\BP\TableScreen;

use AC\TableIdsFactory;
use AC\Type\TableId;
use AC\Type\TableIdCollection;

class TableIds implements TableIdsFactory
{

    public function create(): TableIdCollection
    {
        return new TableIdCollection([
            new TableId('bp-groups'),
            new TableId('bp-activity'),
        ]);
    }

}