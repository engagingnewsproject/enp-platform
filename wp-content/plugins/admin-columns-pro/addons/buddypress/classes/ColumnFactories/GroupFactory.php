<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\BP\ColumnFactory;

class GroupFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ((string)$table_screen->get_id() !== 'bp-groups') {
            return $collection;
        }

        $factories = [
            ColumnFactory\Group\AvatarFactory::class,
            ColumnFactory\Group\CreatorFactory::class,
            ColumnFactory\Group\IdFactory::class,
            ColumnFactory\Group\NameOnlyFactory::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }
}