<?php

declare(strict_types=1);

namespace ACP\Filtering\DefaultFilters;

use AC\TableScreen;

class Media implements ActiveFilters
{

    public function create(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof TableScreen\Media) {
            return [];
        }

        $filters = [];

        if ($this->selected_media_type()) {
            $filters[] = 'attachment-filter';
        }

        return $filters;
    }

    private function selected_media_type(): bool
    {
        return (bool)($_GET['attachment-filter'] ?? false);
    }

}