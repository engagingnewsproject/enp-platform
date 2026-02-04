<?php

declare(strict_types=1);

namespace ACP\Filtering\Service\Table;

use AC\ListScreen;
use AC\Registerable;
use AC\Setting\Component;
use ACP\Column;
use ACP\Filtering\TableScreenFactory;

class FilterContainers implements Registerable
{

    public function register(): void
    {
        add_action('ac/table/list_screen', [$this, 'load']);
    }

    public function load(ListScreen $list_screen): void
    {
        foreach ($list_screen->get_columns() as $column) {
            if ( ! $column instanceof Column) {
                return;
            }

            $setting = $column->get_setting('filter');

            if ( ! $setting instanceof Component) {
                continue;
            }

            if ( ! $setting->has_input() || $setting->get_input()->get_value() !== 'on') {
                continue;
            }

            $comparison = $column->search();

            if ( ! $comparison) {
                continue;
            }

            $table = (new TableScreenFactory())->create(
                $list_screen->get_table_screen(),
                (string)$column->get_id()
            );

            if ( ! $table) {
                continue;
            }

            $table->register();
        }
    }

}