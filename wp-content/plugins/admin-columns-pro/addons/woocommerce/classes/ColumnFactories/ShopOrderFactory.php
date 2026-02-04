<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use ACA\WC\ColumnFactory;

class ShopOrderFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof AC\TableScreen\Post || ! $table_screen->get_post_type()->equals('shop_order')) {
            return $collection;
        }

        $factories = [
            // Order (HPOS)
            ColumnFactory\Order\NotesFactory::class,
            ColumnFactory\Order\PaidAmountFactory::class,

            //Custom
            ColumnFactory\ShopOrder\CouponsUsed::class,
            ColumnFactory\ShopOrder\Currency::class,
            ColumnFactory\ShopOrder\Customer::class,
            ColumnFactory\ShopOrder\CustomerNote::class,
            ColumnFactory\ShopOrder\Discount::class,
            ColumnFactory\ShopOrder\Downloads::class,
            ColumnFactory\ShopOrder\Fees::class,
            ColumnFactory\ShopOrder\Ip::class,
            ColumnFactory\ShopOrder\IsCustomer::class,
            ColumnFactory\ShopOrder\OrderNumber::class,
            ColumnFactory\ShopOrder\OrderDate::class,
            ColumnFactory\ShopOrder\PaymentMethod::class,
            ColumnFactory\ShopOrder\Product::class,
            ColumnFactory\ShopOrder\ProductTaxonomy::class,
            ColumnFactory\ShopOrder\Purchased::class,
            ColumnFactory\ShopOrder\Refunds::class,
            ColumnFactory\ShopOrder\Shipping::class,
            ColumnFactory\ShopOrder\ShippingMethod::class,
            ColumnFactory\ShopOrder\StatusIcon::class,
            ColumnFactory\ShopOrder\Subtotal::class,
            ColumnFactory\ShopOrder\Tax::class,
            ColumnFactory\ShopOrder\Totals::class,
            ColumnFactory\ShopOrder\TotalWeight::class,
            ColumnFactory\ShopOrder\TransactionId::class,
            ColumnFactory\ShopOrder\Address\BillingAddress::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }
}