<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\PostType;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use ACA\SeoPress\ColumnFactory;

class RedirectFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof PostType || ! $table_screen->get_post_type()->equals('seopress_404')) {
            return $collection;
        }

        $factories = [
            ColumnFactory\Redirect\LoginStatus::class,
            ColumnFactory\Redirect\QueryParameters::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}