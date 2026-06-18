<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories;

use AC;
use AC\Collection\ColumnFactories;
use AC\TableScreen;
use AC\Type\TableScreenContext;
use ACA\ACF\FieldRepository;

class FieldsFactory implements AC\ColumnFactoryCollectionFactory
{

    private FieldRepository $field_repository;

    private FieldFactory $field_factory;

    public function __construct(
        FieldRepository $field_repository,
        FieldFactory $field_factory
    ) {
        $this->field_repository = $field_repository;
        $this->field_factory = $field_factory;
    }

    public function create(TableScreen $table_screen): ColumnFactories
    {
        $collection = new AC\Collection\ColumnFactories();

        if ( ! $table_screen instanceof AC\TableScreen\MetaType) {
            return $collection;
        }

        $table_context = TableScreenContext::from_table_screen($table_screen);

        if ( ! $table_context) {
            return $collection;
        }

        foreach ($this->field_repository->find_all($table_screen) as $field) {
            $factory = $this->field_factory->create($table_context, $field);

            if ($factory instanceof AC\Column\ColumnFactory) {
                $collection->add($factory);
            }
        }

        return $collection;
    }

}
