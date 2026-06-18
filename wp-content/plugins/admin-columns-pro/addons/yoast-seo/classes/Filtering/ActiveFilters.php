<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Filtering;

use AC\PostType;
use AC\TableScreen;
use ACP;

class ActiveFilters implements ACP\Filtering\DefaultFilters\ActiveFilters
{

    public function create(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof PostType) {
            return [];
        }

        $filters = [];

        if ($this->selected('seo_filter')) {
            $filters[] = 'seo_filter';
        }

        if ($this->selected('readability_filter')) {
            $filters[] = 'readability_filter';
        }

        return $filters;
    }

    private function selected(string $name): bool
    {
        return isset($_GET[$name])
               && is_string($_GET[$name])
               && sanitize_text_field(wp_unslash($_GET[$name]));
    }

}