<?php

declare(strict_types=1);

namespace ACP\Editing\Factory;

use AC;
use AC\ColumnCollection;
use ACP\Column;
use ACP\Editing\ApplyFilter\BulkEditActive;
use ACP\Editing\Service;
use ACP\Editing\Strategy\AggregateFactory;
use ACP\Editing\TableElement;

class BulkEditFactory
{

    private AggregateFactory $aggregate_factory;

    public function __construct(
        AggregateFactory $aggregate_factory
    ) {
        $this->aggregate_factory = $aggregate_factory;
    }

    public function create(AC\ListScreen $list_screen): ColumnCollection
    {
        $columns = new ColumnCollection();

        if ( ! $this->is_list_screen_editable($list_screen)) {
            return $columns;
        }

        foreach ($list_screen->get_columns() as $column) {
            if ($this->is_column_bulk_editable($column, $list_screen)) {
                $columns->add($column);
            }
        }

        return $columns;
    }

    private function is_list_screen_editable(AC\ListScreen $list_screen): bool
    {
        $strategy = $this->aggregate_factory->create(
            $list_screen->get_table_screen()
        );

        if ( ! $strategy || ! $strategy->user_can_edit()) {
            return false;
        }

        return (new TableElement\BulkEdit())->is_enabled($list_screen);
    }

    public function is_column_bulk_editable(AC\Column $column, AC\ListScreen $list_screen): bool
    {
        if ( ! $column instanceof Column) {
            return false;
        }

        $service = $column->editing();

        if ( ! $service) {
            return false;
        }

        if ( ! $service->get_view(Service::CONTEXT_BULK)) {
            return false;
        }

        $component = $column->get_setting('bulk_edit');

        if ( ! $component) {
            return false;
        }

        $filter = new BulkEditActive(
            $column->get_context(),
            $list_screen
        );

        return $filter->apply_filters(
            $component->get_input()->get_value() === 'on'
        );
    }

}