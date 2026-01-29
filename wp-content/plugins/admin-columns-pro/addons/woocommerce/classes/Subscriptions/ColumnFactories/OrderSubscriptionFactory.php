<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\WC\ColumnFactory;
use ACA\WC\Subscriptions\ColumnFactory\OrderSubscription;

class OrderSubscriptionFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();
        if ( ! $table_screen instanceof \ACA\WC\Subscriptions\TableScreen\OrderSubscription) {
            return $collection;
        }

        $factories = [
            // Order Columns
            ColumnFactory\Order\Date\CompletedDateFactory::class,
            ColumnFactory\Order\Date\CreatedDateFactory::class,
            ColumnFactory\Order\Date\ModifiedDateFactory::class,
            ColumnFactory\Order\Date\PaidDateFactory::class,
            ColumnFactory\Order\Address\BillingAddressFactory::class,
            ColumnFactory\Order\Address\ShippingAddressFactory::class,
            ColumnFactory\Order\CouponsUsedFactory::class,
            ColumnFactory\Order\CreatedVersionFactory::class,
            ColumnFactory\Order\CreatedViaFactory::class,
            ColumnFactory\Order\CurrencyFactory::class,
            ColumnFactory\Order\CustomerFactory::class,
            ColumnFactory\Order\CustomerNoteFactory::class,
            ColumnFactory\Order\DiscountTotalFactory::class,
            ColumnFactory\Order\DiscountTaxFactory::class,
            ColumnFactory\Order\FeesFactory::class,
            ColumnFactory\Order\NotesFactory::class,
            ColumnFactory\Order\OrderIdFactory::class,
            ColumnFactory\Order\OrderNumberFactory::class,
            ColumnFactory\Order\PaymentMethodFactory::class,
            ColumnFactory\Order\ProductTaxonomyFactory::class,
            ColumnFactory\Order\PurchasedFactory::class,
            ColumnFactory\Order\RefundFactory::class,
            ColumnFactory\Order\ShippingFactory::class,
            ColumnFactory\Order\ShippingMethodFactory::class,
            ColumnFactory\Order\SubtotalFactory::class,
            ColumnFactory\Order\OrderMetaFactory::class,

            // Subscription Columns
            OrderSubscription\AutoRenewal::class,
            OrderSubscription\BillingInterval::class,
            OrderSubscription\BillingPeriod::class,
            OrderSubscription\Product::class,
            OrderSubscription\TotalRevenue::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}