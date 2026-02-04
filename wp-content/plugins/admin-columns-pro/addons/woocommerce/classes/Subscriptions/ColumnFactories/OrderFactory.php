<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\WC;
use ACA\WC\Subscriptions\ColumnFactory;

class OrderFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();
        if ( ! $table_screen instanceof WC\TableScreen\Order) {
            return $collection;
        }

        $factories = [
            ColumnFactory\Order\RelatedSubscription::class,
            ColumnFactory\Order\SubscriptionOrderType::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}