<?php

declare(strict_types=1);

namespace ACA\Polylang;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;

class ColumnTypesFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ($table_screen instanceof AC\PostType || $table_screen instanceof AC\Taxonomy) {
            $collection->add(new AC\Type\ColumnFactoryDefinition(ColumnFactory\Language::class));
        }

        return $collection;
    }

}