<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA\BP\ColumnFactory;
use ACA\BP\FieldRepository;

class ProfileFieldsFactory extends AC\ColumnFactories\BaseFactory
{

    private FieldRepository $field_repository;

    public function __construct(
        Container $container,
        FieldRepository $field_repository
    ) {
        parent::__construct($container);

        $this->field_repository = $field_repository;
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof TableScreen\User) {
            return $collection;
        }

        foreach ($this->field_repository->find_all() as $field) {
            $type = 'column-acp_bp_profile_' . $field->id;

            $collection->add(
                new AC\Type\ColumnFactoryDefinition(
                    ColumnFactory\User\ProfileFieldFactory::class,
                    [
                        'type'  => $type,
                        'field' => $field,
                    ]
                )
            );
        }

        return $collection;
    }

}