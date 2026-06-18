<?php

declare(strict_types=1);

namespace ACA\MetaBox\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\MetaBox\ColumnFactory;

class PostTypeFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post ||
             ! $table_screen->get_post_type()->equals('mb-post-type')) {
            return $collection;
        }

        $factories = [
            ColumnFactory\PostType\Description::class,
            ColumnFactory\PostType\Label::class,
            ColumnFactory\PostType\Supports::class,
            ColumnFactory\PostType\Taxonomies::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}