<?php

namespace ACP\Sorting;

use AC\Column;
use AC\TableScreen;
use ACP;
use ACP\Sorting\Model\QueryBindings;

class ModelFactory
{

    public function create(Column $column, TableScreen $table_screen): ?QueryBindings
    {
        if ( ! $column instanceof ACP\Column) {
            return null;
        }

        $bindings = apply_filters(
            'ac/sorting/model',
            $column->sorting(),
            $column->get_context(),
            $table_screen
        );

        return $bindings instanceof QueryBindings
            ? $bindings
            : null;
    }

}