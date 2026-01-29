<?php

declare(strict_types=1);

namespace ACA\JetEngine\ColumnFactories;

use AC\ColumnFactories\BaseFactory;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use AC\Type\TableScreenContext;
use AC\Vendor\DI\Container;
use ACA\JetEngine\ColumnFactory;
use ACA\JetEngine\Field;
use ACA\JetEngine\FieldRepository;

class MetaFactory extends BaseFactory
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
        $fields = $this->field_repository->find_all($table_screen);

        if (empty($fields)) {
            return $collection;
        }

        $context = TableScreenContext::from_table_screen($table_screen);

        if ( ! $context) {
            return $collection;
        }

        $mapping = [
            Field\Type\Repeater::TYPE => ColumnFactory\Meta\Repeater::class,
        ];

        foreach ($fields as $field) {
            $arguments = [
                'column_type'   => 'column-jetengine-' . $field->get_name(),
                'label'         => $field->get_title(),
                'field'         => $field,
                'table_context' => $context,
            ];

            $factory_class = array_key_exists($field->get_type(), $mapping)
                ? $mapping[$field->get_type()]
                : ColumnFactory\Meta\FieldFactory::class;

            $collection->add(new ColumnFactoryDefinition($factory_class, $arguments));
        }

        return $collection;
    }

}