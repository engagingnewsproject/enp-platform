<?php

declare(strict_types=1);

namespace ACP\Filtering\DefaultFilters;

use AC\PostType;
use AC\TableScreen;

class Post implements ActiveFilters
{

    public function create(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof PostType) {
            return [];
        }

        $filters = [];

        if ($this->selected_category_filter()) {
            $filters[] = 'cat';
        }
        if ($this->selected_formats_filter()) {
            $filters[] = 'post_format';
        }
        if ($this->selected_date_filter()) {
            $filters[] = 'm';
        }

        return $filters;
    }

    private function selected_category_filter(): bool
    {
        return is_category() && get_query_var('cat') > 0;
    }

    private function selected_formats_filter(): bool
    {
        $post_format = $_GET['post_format'] ?? null;

        return (bool)$post_format;
    }

    private function selected_date_filter(): bool
    {
        $month = $_GET['m'] ?? 0;

        return (int)$month > 0;
    }

}