<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\BP\ColumnFactory;

class UserFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof TableScreen\User) {
            return $collection;
        }

        $factories = [
            ColumnFactory\User\LastSeenFactory::class,
            ColumnFactory\User\StatusFactory::class,
        ];

        if (bp_is_active('friends')) {
            $factories[] = ColumnFactory\User\FriendsFactory::class;
        }

        if (bp_is_active('activity')) {
            $factories[] = ColumnFactory\User\ActivityUpdatesFactory::class;
            $factories[] = ColumnFactory\User\LastActivityFactory::class;
        }

        if (bp_is_active('groups')) {
            $factories[] = ColumnFactory\User\GroupsFactory::class;
        }

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}