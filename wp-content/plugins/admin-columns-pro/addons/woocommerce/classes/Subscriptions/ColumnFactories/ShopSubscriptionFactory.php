<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\TableId;
use ACA\WC\ColumnFactory;
use ACA\WC\Subscriptions\ColumnFactory\ShopSubscription;

class ShopSubscriptionFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen->get_id()->equals(new TableId('shop_subscription'))) {
            return $collection;
        }

        $factories = [
            // Order Columns
            ColumnFactory\ShopOrder\Address\BillingAddress::class,
            ColumnFactory\ShopOrder\Address\ShippingAddress::class,
            ColumnFactory\ShopOrder\Customer::class,
            ColumnFactory\ShopOrder\CouponsUsed::class,
            ColumnFactory\ShopOrder\Currency::class,
            ColumnFactory\ShopOrder\Discount::class,
            ColumnFactory\ShopOrder\Downloads::class,
            ColumnFactory\ShopOrder\Product::class,
            ColumnFactory\ShopOrder\Purchased::class,
            ColumnFactory\ShopOrder\Subtotal::class,
            ColumnFactory\ShopOrder\Tax::class,
            ColumnFactory\ShopOrder\Totals::class,

            // Subscription Columns
            ShopSubscription\AutoRenewal::class,
            ShopSubscription\TotalRevenue::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }
}