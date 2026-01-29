<?php

declare(strict_types=1);

namespace ACA\WC\Filtering\DefaultFilters;

use AC\TableScreen;
use ACA\WC;
use ACP\Filtering\DefaultFilters\ActiveFilters;

class Order implements ActiveFilters
{

    public function create(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof WC\TableScreen\Order) {
            return [];
        }

        $filters = [];

        if ($this->selected_date_filter()) {
            $filters[] = 'm';
        }
        if ($this->selected_sales_channel()) {
            $filters[] = '_created_via';
        }

        return $filters;
    }

    private function selected_date_filter(): bool
    {
        $month = $_GET['m'] ?? 0;

        return (int)$month > 0;
    }

    private function selected_sales_channel(): bool
    {
        return isset($_GET['_created_via']) && sanitize_text_field(wp_unslash($_GET['_created_via']));
    }

}