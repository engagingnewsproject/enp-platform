<?php

namespace ACP\QuickAdd\Admin;

use AC\Registerable;
use AC\TableScreen;
use ACP\QuickAdd\Filter;
use ACP\QuickAdd\Model\Factory;
use ACP\Settings\ListScreen\TableElements;

class Settings implements Registerable
{

    private $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function register(): void
    {
        add_action('ac/admin/settings/table_elements', [$this, 'add_table_elements'], 10, 2);
    }

    public function add_table_elements(TableElements $collection, TableScreen $table_screen)
    {
        if ( ! $this->filter->match($table_screen)) {
            return;
        }

        $model = Factory::create($table_screen);

        if ( ! $model || ! $model->has_permission(wp_get_current_user())) {
            return;
        }

        $collection->add(new TableElement\QuickAdd(), 60);
    }

}