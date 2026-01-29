<?php

declare(strict_types=1);

namespace ACP\Search;

use AC;

class TableScreenSupport
{

    public static function is_searchable(AC\TableScreen $table_screen): bool
    {
        return null !== TableMarkupFactory::get_table_markup_reference($table_screen);
    }

}