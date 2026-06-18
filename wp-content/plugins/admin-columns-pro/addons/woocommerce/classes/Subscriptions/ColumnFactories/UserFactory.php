<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\WC\Subscriptions\ColumnFactory;

class UserFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\User) {
            return $collection;
        }

        $factories = [
            ColumnFactory\User\InactiveSubscriber::class,
            ColumnFactory\User\Subscriptions::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}