<?php

declare(strict_types=1);

namespace ACP\Editing\Registerable;

use AC;
use AC\Registerable;
use ACP\Editing;
use ACP\Settings\ListScreen\TableElements;

class AdminTableElements implements Registerable
{

    private Editing\Strategy\AggregateFactory $aggregate_factory;

    private Editing\BulkDelete\AggregateFactory $aggregate_factory_delete;

    public function __construct(
        Editing\Strategy\AggregateFactory $aggregate_factory,
        Editing\BulkDelete\AggregateFactory $aggregate_factory_delete
    ) {
        $this->aggregate_factory = $aggregate_factory;
        $this->aggregate_factory_delete = $aggregate_factory_delete;
    }

    public function register(): void
    {
        add_action('ac/admin/settings/table_elements', [$this, 'add_table_elements'], 10, 2);
    }

    public function add_table_elements(TableElements $collection, AC\TableScreen $table_screen): void
    {
        $edit_strategy = $this->aggregate_factory->create($table_screen);
        $delete_strategy = $this->aggregate_factory_delete->create($table_screen);

        if ($edit_strategy) {
            $collection->add(new Editing\TableElement\InlineEdit())
                       ->add(new Editing\TableElement\BulkEdit(), 20);
        }
        if ($delete_strategy) {
            $collection->add(new Editing\TableElement\BulkDelete(), 30);
        }
    }

}