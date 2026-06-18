<?php

declare(strict_types=1);

namespace ACA\WC\Service;

use AC\ListScreen;
use AC\Registerable;
use ACP\Settings\ListScreen\TableElement;

class HideProductFilter implements Registerable
{

    private ListScreen $list_screen;

    private TableElement $table_element;

    private string $filter_name;

    public function __construct(ListScreen $list_screen, TableElement $table_element, string $filter_name)
    {
        $this->list_screen = $list_screen;
        $this->table_element = $table_element;
        $this->filter_name = $filter_name;
    }

    public function register(): void
    {
        add_filter('woocommerce_products_admin_list_table_filters', [$this, 'hide_filter']);
    }

    public function hide_filter($filters)
    {
        if ( ! $this->table_element->is_enabled($this->list_screen)) {
            unset($filters[$this->filter_name]);
        }

        return $filters;
    }

}