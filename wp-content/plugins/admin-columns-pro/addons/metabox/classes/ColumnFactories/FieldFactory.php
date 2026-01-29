<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use AC\Type\TableScreenContext;
use AC\Vendor\DI\Container;
use ACA\MetaBox\ColumnFactory;
use ACA\MetaBox\Field\Type\Group;
use ACA\MetaBox\FieldRepository;

class FieldFactory extends AC\ColumnFactories\BaseFactory
{

    private FieldRepository $field_repository;

    public function __construct(Container $container, FieldRepository $field_repository)
    {
        parent::__construct($container);

        $this->field_repository = $field_repository;
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();
        $table_context = TableScreenContext::from_table_screen($table_screen);

        foreach ($this->field_repository->find_all($table_screen) as $field) {
            $is_relation = 1 === (int)$field->get_setting('relationship', 0);

            if ($is_relation) {
                continue;
            }

            $arguments = [
                'column_type'   => 'column-metabox-' . $field->get_id(),
                'label'         => $field->get_name(),
                'field'         => $field,
                'table_context' => $table_context,
            ];

            switch (true) {
                case $field instanceof Group:
                    if ($field->is_cloneable()) {
                        $collection->add(
                            new ColumnFactoryDefinition(
                                ColumnFactory\Meta\GroupCloneFactory::class, $arguments
                            )
                        );
                    } else {
                        $collection->add(
                            new ColumnFactoryDefinition(
                                ColumnFactory\Meta\GroupFactory::class, $arguments
                            )
                        );
                    }
                    break;
                default:
                    $class = $field->is_cloneable()
                        ? ColumnFactory\Meta\CloneFieldFactory::class
                        : ColumnFactory\Meta\FieldFactory::class;

                    $collection->add(new ColumnFactoryDefinition($class, $arguments));
            }
        }

        return $collection;
    }

}