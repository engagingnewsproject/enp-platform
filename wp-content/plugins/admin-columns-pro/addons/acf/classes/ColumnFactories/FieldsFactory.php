<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories;

use AC;
use AC\Collection\ColumnFactories;
use AC\TableScreen;
use AC\Type\TableScreenContext;
use ACA\ACF;
use ACA\ACF\FieldRepository;

class FieldsFactory implements AC\ColumnFactoryCollectionFactory
{

    private FieldRepository $field_repository;

    private GroupFieldFactory $group_field_factory;

    private FieldFactory $field_factory_factory;

    private CloneFieldFactory $clone_field_factory_factory;

    public function __construct(
        FieldRepository $field_repository,
        FieldFactory $field_factory_factory,
        GroupFieldFactory $group_field_factory,
        CloneFieldFactory $clone_field_factory_factory
    ) {
        $this->field_repository = $field_repository;
        $this->group_field_factory = $group_field_factory;
        $this->field_factory_factory = $field_factory_factory;
        $this->clone_field_factory_factory = $clone_field_factory_factory;
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

        /**
         * @var $field ACF\Field
         */
        foreach ($this->field_repository->find_all($table_screen) as $field) {
            switch (true) {
                case $field->is_clone():
                    $factory = $this->clone_field_factory_factory->create($table_context, $field);
                    break;
                case $field instanceof ACF\Field\Type\GroupSubField:
                    $factory = $this->group_field_factory->create($table_context, $field);

                    break;
                default:
                    $factory = $this->field_factory_factory->create($table_context, $field);
            }

            if ($factory instanceof AC\Column\ColumnFactory) {
                $collection->add($factory);
            }
        }

        return $collection;
    }

}