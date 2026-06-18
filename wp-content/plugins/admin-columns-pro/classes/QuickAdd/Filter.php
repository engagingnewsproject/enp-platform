<?php

namespace ACP\QuickAdd;

use AC\TableScreen;

class Filter
{

    public function match(TableScreen $table_screen): bool
    {
        return (bool)apply_filters('ac/quick_add/enable', true, $table_screen);
    }

}