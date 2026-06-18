<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\EC\ColumnFactory;

class VenueFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post
             || ! $table_screen->get_post_type()->equals('tribe_venue')) {
            return $collection;
        }

        $factories = [
            ColumnFactory\Venue\CountryFactory::class,
            ColumnFactory\Venue\EventsFactory::class,
            ColumnFactory\Venue\UpcomingEventFactory::class,
            ColumnFactory\Venue\WebsiteFactory::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        $meta_fields = [
            [
                'column_type' => 'column-ec-venue_address',
                'label'       => __('Address', 'codepress-admin-columns'),
                'meta_key'    => '_VenueAddress',
            ],
            [
                'column_type' => 'column-ec-venue_city',
                'label'       => __('City', 'codepress-admin-columns'),
                'meta_key'    => '_VenueCity',
            ],
            [
                'column_type' => 'column-ec-venue_postal_code',
                'label'       => __('Postal Code', 'codepress-admin-columns'),
                'meta_key'    => '_VenueZip',
            ],
            [
                'column_type' => 'column-ec-venue_phone',
                'label'       => __('Phone', 'codepress-admin-columns'),
                'meta_key'    => '_VenuePhone',
            ],
            [
                'column_type' => 'column-ec-venue_stateprovince',
                'label'       => __('State or Province', 'codepress-admin-columns'),
                'meta_key'    => '_VenueStateProvince',
            ],
        ];

        foreach ($meta_fields as $arguments) {
            $collection->add(
                new AC\Type\ColumnFactoryDefinition(
                    ColumnFactory\MetaTextFieldFactory::class, $arguments
                )
            );
        }

        return $collection;
    }
}