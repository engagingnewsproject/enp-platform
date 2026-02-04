<?php

declare(strict_types=1);

namespace ACA\WC\Filtering\DefaultFilters;

use AC\PostType;
use AC\TableScreen;
use ACP\Filtering\DefaultFilters\ActiveFilters;

class ProductVariation implements ActiveFilters
{

    public function create(TableScreen $table_screen): array
    {
        if ( ! ($table_screen instanceof PostType && 'product_variation' === (string)$table_screen->get_post_type())) {
            return [];
        }

        $filters = [];

        if ($this->selected_product_parent()) {
            $filters[] = 'post_parent';
        }

        return $filters;
    }

    private function selected_product_parent(): bool
    {
        return isset($_REQUEST['post_parent']) && wc_clean(wp_unslash($_REQUEST['post_parent']));
    }

}