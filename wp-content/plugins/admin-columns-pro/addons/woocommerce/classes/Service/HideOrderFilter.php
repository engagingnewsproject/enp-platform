<?php

declare(strict_types=1);

namespace ACA\WC\Service;

use AC\ListScreen;
use AC\Registerable;
use ACP\Settings\ListScreen\TableElement;

class HideOrderFilter implements Registerable
{

    private ListScreen $list_screen;

    private TableElement $table_element;

    public function __construct(ListScreen $list_screen, TableElement $table_element)
    {
        $this->list_screen = $list_screen;
        $this->table_element = $table_element;
    }

    public function register(): void
    {
        add_filter('admin_body_class', [$this, 'hide_filter']);
    }

    public function hide_filter($class)
    {
        if ( ! $this->table_element->is_enabled($this->list_screen)) {
            $class .= ' ac-filter-' . $this->table_element->get_name();
        }

        return $class;
    }

}