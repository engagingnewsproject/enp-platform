<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\EC\ColumnFactory;

class OrganizerFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post ||
             ! $table_screen->get_post_type()->equals('tribe_organizer')) {
            return $collection;
        }

        $factories = [
            ColumnFactory\Organizer\EmailFactory::class,
            ColumnFactory\Organizer\EventsFactory::class,
            ColumnFactory\Organizer\PhoneFactory::class,
            ColumnFactory\Organizer\UpcomingEventFactory::class,
            ColumnFactory\Organizer\WebsiteFactory::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}