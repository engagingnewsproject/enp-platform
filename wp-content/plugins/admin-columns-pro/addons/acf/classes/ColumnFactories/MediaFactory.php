<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\ACF\ColumnFactory\Media\UsedInAcfField;

class MediaFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Media) {
            return $collection;
        }

        $collection->add(new AC\Type\ColumnFactoryDefinition(UsedInAcfField::class));

        return $collection;
    }

}
