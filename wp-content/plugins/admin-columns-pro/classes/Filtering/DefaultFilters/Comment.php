<?php

declare(strict_types=1);

namespace ACP\Filtering\DefaultFilters;

use AC\TableScreen;

class Comment implements ActiveFilters
{

    public function create(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof TableScreen\Comment) {
            return [];
        }

        $filters = [];

        if ($this->selected_comment_type()) {
            $filters[] = 'comment_type';
        }

        return $filters;
    }

    private function selected_comment_type(): bool
    {
        global $comment_type;

        return (bool)$comment_type;
    }

}