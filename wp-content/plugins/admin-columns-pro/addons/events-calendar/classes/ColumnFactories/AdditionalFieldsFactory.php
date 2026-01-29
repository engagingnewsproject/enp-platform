<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\EC\API;
use ACA\EC\ColumnFactory;

final class AdditionalFieldsFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post ||
             ! $table_screen->get_post_type()->equals('tribe_events')) {
            return $collection;
        }

        if ( ! API::is_pro()) {
            return $collection;
        }

        foreach (API::get_additional_fields() as $field) {
            $collection->add(
                new AC\Type\ColumnFactoryDefinition(
                    ColumnFactory\Event\AdditionalFields\AdditionalFieldFactory::class,
                    [
                        'field' => $field,
                        'type'  => 'column-ec-event-additional-' . $field->get_id(),
                    ]
                )
            );
        }

        return $collection;
    }

}