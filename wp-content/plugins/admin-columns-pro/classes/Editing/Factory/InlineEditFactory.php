<?php

declare(strict_types=1);

namespace ACP\Editing\Factory;

use AC;
use AC\ColumnCollection;
use ACP\Column;
use ACP\Editing\Service;
use ACP\Editing\Strategy\AggregateFactory;
use ACP\Table\TableSupport;

class InlineEditFactory
{

    private AggregateFactory $aggregate_factory;

    public function __construct(AggregateFactory $aggregate_factory)
    {
        $this->aggregate_factory = $aggregate_factory;
    }

    public function create(AC\ListScreen $list_screen): ColumnCollection
    {
        return $this->is_list_screen_editable($list_screen)
            ? new ColumnCollection(
                array_filter(iterator_to_array($list_screen->get_columns()), [$this, 'is_column_inline_editable'])
            )
            : new ColumnCollection();
    }

    public function is_list_screen_editable(AC\ListScreen $list_screen): bool
    {
        $strategy = $this->aggregate_factory->create(
            $list_screen->get_table_screen()
        );

        if ( ! $strategy || ! $strategy->user_can_edit()) {
            return false;
        }

        return TableSupport::is_inline_edit_enabled($list_screen);
    }

    public function is_column_inline_editable(AC\Column $column): bool
    {
        if ( ! $column instanceof Column) {
            return false;
        }

        $service = $column->editing();

        if ( ! $service) {
            return false;
        }

        if ( ! $service->get_view(Service::CONTEXT_SINGLE)) {
            return false;
        }

        $setting = $column->get_setting('edit');

        if ($setting === null) {
            return false;
        }

        return 'on' === $setting->get_input()->get_value();
    }

}