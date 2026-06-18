<?php

declare(strict_types=1);

namespace ACP\Filtering\DefaultFilters;

use AC\TableScreen;

interface ActiveFilters
{

    /**
     * @param TableScreen $table_screen
     *
     * @return array Value of the 'name' attribute from the select-element e.g. 'cat', 'm'
     */
    public function create(TableScreen $table_screen): array;

}