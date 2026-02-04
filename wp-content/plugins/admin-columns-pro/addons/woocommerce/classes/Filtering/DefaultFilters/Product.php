<?php

declare(strict_types=1);

namespace ACA\WC\Filtering\DefaultFilters;

use AC\PostType;
use AC\TableScreen;
use ACP\Filtering\DefaultFilters\ActiveFilters;

class Product implements ActiveFilters
{

    public function create(TableScreen $table_screen): array
    {
        if ( ! ($table_screen instanceof PostType && 'product' === (string)$table_screen->get_post_type())) {
            return [];
        }

        $filters = [];

        if ($this->selected_product_type()) {
            $filters[] = 'product_type';
        }
        if ($this->selected_product_category()) {
            $filters[] = 'product_cat';
        }
        if ($this->selected_stock()) {
            $filters[] = 'stock_status';
        }
        if ($this->selected_product_brand()) {
            $filters[] = 'product_brand';
        }

        return $filters;
    }

    private function selected_product_category(): bool
    {
        return isset($_GET['product_cat']) && wc_clean(wp_unslash($_GET['product_cat']));
    }

    private function selected_product_type(): bool
    {
        return isset($_REQUEST['product_type']) && wc_clean(wp_unslash($_REQUEST['product_type']));
    }

    private function selected_stock(): bool
    {
        return isset($_REQUEST['stock_status']) && wc_clean(wp_unslash($_REQUEST['stock_status']));
    }

    private function selected_product_brand(): bool
    {
        return (bool)wc_clean(wp_unslash($_GET['product_brand'] ?? ''));
    }

}