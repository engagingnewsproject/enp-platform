<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACP\Export\Strategy\AggregateFactory;

abstract class OriginalAdvancedColumnFactory extends AC\ColumnFactories\BaseFactory
{

    private OriginalColumnsRepository $original_columns_repository;

    private AggregateFactory $export_strategy_factory;

    abstract protected function get_original_factories(TableScreen $table_screen): array;

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
        $factories = $this->get_original_factories($table_screen);

        if (empty($factories)) {
            return $collection;
        }

        $add_export = null !== $this->export_strategy_factory->create($table_screen);

        foreach ($this->original_columns_repository->find_all_cached($table_screen->get_id()) as $column) {
            $type = $column->get_name();

            $defaults = [
                'type'       => $type,
                'label'      => $column->get_label(),
                'add_sort'   => $column->is_sortable(),
                'add_export' => $add_export,
            ];

            if (array_key_exists($type, $factories)) {
                $collection->add(
                    new AC\Type\ColumnFactoryDefinition($factories[$type], $defaults)
                );
            }
        }

        return $collection;
    }

}