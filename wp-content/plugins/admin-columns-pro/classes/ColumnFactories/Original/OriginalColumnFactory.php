<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use AC\Vendor\DI\Container;
use ACP;
use ACP\Export\Strategy\AggregateFactory;

final class OriginalColumnFactory extends AC\ColumnFactories\BaseFactory
{

    private OriginalColumnsRepository $original_columns_repository;

    private ACP\Export\StrategyFactory $export_strategy_factory;

    public function __construct(
        Container $container,
        OriginalColumnsRepository $original_columns_repository,
        AggregateFactory $export_strategy_factory
    ) {
        parent::__construct($container);

        $this->original_columns_repository = $original_columns_repository;
        $this->export_strategy_factory = $export_strategy_factory;
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        $add_export = null !== $this->export_strategy_factory->create($table_screen);

        foreach ($this->original_columns_repository->find_all_cached($table_screen->get_id()) as $column) {
            $collection->add(
                new ColumnFactoryDefinition(
                    ACP\Column\OriginalColumnFactory::class,
                    [
                        'type'       => $column->get_name(),
                        'label'      => $column->get_label(),
                        'add_sort'   => $column->is_sortable(),
                        'add_export' => $add_export,
                    ]
                )
            );
        }

        return $collection;
    }

}