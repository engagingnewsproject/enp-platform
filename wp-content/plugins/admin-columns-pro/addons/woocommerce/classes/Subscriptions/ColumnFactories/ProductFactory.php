<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\WC\Subscriptions\ColumnFactory;

class ProductFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post || ! $table_screen->get_post_type()->equals('product')) {
            return $collection;
        }

        $factories = [
            ColumnFactory\Product\Expires::class,
            ColumnFactory\Product\FreeTrial::class,
            ColumnFactory\Product\LimitSubscription::class,
            ColumnFactory\Product\Period::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}