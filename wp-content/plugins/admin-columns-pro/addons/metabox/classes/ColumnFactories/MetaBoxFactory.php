<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\MetaBox\ColumnFactory;

class MetaBoxFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post || ! $table_screen->get_post_type()->equals('meta-box')) {
            return $collection;
        }

        $factories = [
            ColumnFactory\MetaBox\CustomTable::class,
            ColumnFactory\MetaBox\Fields::class,
            ColumnFactory\MetaBox\Id::class,
            ColumnFactory\MetaBox\NumberOfFields::class,
            ColumnFactory\MetaBox\Position::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}