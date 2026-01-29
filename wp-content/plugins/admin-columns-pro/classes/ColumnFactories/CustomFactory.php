<?php

declare(strict_types=1);

namespace ACP\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;

final class CustomFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();
        $factory_classes = apply_filters('ac/column/types/pro', [], $table_screen);

        foreach ($factory_classes as $factory => $props) {
            if (is_numeric($factory)) {
                $factory = $props;
                $props = [];
            }

            $collection->add(
                new ColumnFactoryDefinition(
                    $factory, $props
                )
            );
        }

        return $collection;
    }

}