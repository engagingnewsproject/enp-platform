<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories\Original;

use AC\TableScreen;
use ACA\WC\ColumnFactory;
use ACA\WC\TableScreen\Order;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class OrderFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof Order) {
            return [];
        }

        return [
            'billing_address'  => ColumnFactory\Order\Original\BillingAddressFactory::class,
            'order_date'       => ColumnFactory\Order\Original\OrderDateFactory::class,
            'order_number'     => ColumnFactory\Order\Original\OrderNumberFactory::class,
            'shipping_address' => ColumnFactory\Order\Original\ShipToFactory::class,
            'order_status'     => ColumnFactory\Order\Original\StatusFactory::class,
            'order_total'      => ColumnFactory\Order\Original\TotalFactory::class,
        ];
    }
}