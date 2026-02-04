<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Type\TableId;
use ACA\WC\ColumnFactory;
use ACA\WC\TableScreen\Order;

class OrderFactory extends AC\ColumnFactories\BaseFactory
{

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof Order) {
            return $collection;
        }

        $this->container->set(TableId::class, $table_screen->get_id());

        $factories = [
            //Date
            ColumnFactory\Order\Date\CompletedDateFactory::class,
            ColumnFactory\Order\Date\CreatedDateFactory::class,
            ColumnFactory\Order\Date\ModifiedDateFactory::class,
            ColumnFactory\Order\Date\PaidDateFactory::class,

            //Address
            ColumnFactory\Order\Address\BillingAddressFactory::class,
            ColumnFactory\Order\Address\ShippingAddressFactory::class,

            //Custom
            ColumnFactory\Order\CouponsUsedFactory::class,
            ColumnFactory\Order\CreatedVersionFactory::class,
            ColumnFactory\Order\CreatedViaFactory::class,
            ColumnFactory\Order\CurrencyFactory::class,
            ColumnFactory\Order\CustomerFactory::class,
            ColumnFactory\Order\CustomerNoteFactory::class,
            ColumnFactory\Order\CustomerTotalSalesFactory::class,
            ColumnFactory\Order\CustomerTotalOrdersFactory::class,
            ColumnFactory\Order\DiscountTotalFactory::class,
            ColumnFactory\Order\DiscountTaxFactory::class,
            ColumnFactory\Order\DownloadPermissionGrantedFactory::class,
            ColumnFactory\Order\DownloadsFactory::class,
            ColumnFactory\Order\FeesFactory::class,
            ColumnFactory\Order\IpFactory::class,
            ColumnFactory\Order\IsCustomerFactory::class,
            ColumnFactory\Order\NotesFactory::class,
            ColumnFactory\Order\OrderIdFactory::class,
            ColumnFactory\Order\OrderKeyFactory::class,
            ColumnFactory\Order\OrderNumberFactory::class,
            ColumnFactory\Order\PaidAmountFactory::class,
            ColumnFactory\Order\PaymentMethodFactory::class,
            ColumnFactory\Order\ProductFactory::class,
            ColumnFactory\Order\ProductTaxonomyFactory::class,
            ColumnFactory\Order\PurchasedFactory::class,
            ColumnFactory\Order\RefundFactory::class,
            ColumnFactory\Order\ReturningCustomerFactory::class,
            ColumnFactory\Order\ShippingFactory::class,
            ColumnFactory\Order\ShippingMethodFactory::class,
            ColumnFactory\Order\ShippingTaxAmountFactory::class,
            ColumnFactory\Order\SubtotalFactory::class,
            ColumnFactory\Order\TaxFactory::class,
            ColumnFactory\Order\TotalWeightFactory::class,
            ColumnFactory\Order\TransactionId::class,
            ColumnFactory\Order\UserAgentFactory::class,
            ColumnFactory\Order\OrderMetaFactory::class,
        ];

        foreach ($factories as $factory) {
            $collection->add(new AC\Type\ColumnFactoryDefinition($factory));
        }

        return $collection;
    }

}

