<?php

declare(strict_types=1);

namespace ACA\RankMath\Filtering;

use AC\PostType;
use AC\TableScreen;
use ACP\Filtering\DefaultFilters\ActiveFilters;

class DefaultActiveFilters implements ActiveFilters
{

    public function create(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof PostType) {
            return [];
        }

        $filters = [];

        if ($this->selected_rankmath_filter()) {
            $filters[] = 'seo-filter';
        }

        return $filters;
    }

    private function selected_rankmath_filter(): bool
    {
        return filter_has_var(INPUT_GET, 'seo-filter') && filter_input(INPUT_GET, 'seo-filter');
    }

}