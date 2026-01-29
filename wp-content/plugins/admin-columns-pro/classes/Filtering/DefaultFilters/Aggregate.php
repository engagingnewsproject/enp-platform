<?php

declare(strict_types=1);

namespace ACP\Filtering\DefaultFilters;

use AC\TableScreen;

class Aggregate implements ActiveFilters
{

    private static array $filters = [];

    public static function add(ActiveFilters $filters): void
    {
        self::$filters[] = $filters;
    }

    public function create(TableScreen $table_screen): array
    {
        $active_filters = [];

        foreach (self::$filters as $item) {
            $active_filters = array_merge($active_filters, $item->create($table_screen));
        }

        return $active_filters;
    }

}