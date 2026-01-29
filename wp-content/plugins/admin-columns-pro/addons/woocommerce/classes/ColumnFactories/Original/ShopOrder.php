<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories\Original;

use AC;
use AC\Collection\ColumnFactories;
use AC\ColumnFactoryCollectionFactory;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA\WC\ColumnFactory\ShopOrder\Original;

class ShopOrder
    implements ColumnFactoryCollectionFactory
{

    private $container;

    private $original_columns_repository;

    public function __construct(Container $container, OriginalColumnsRepository $original_columns_repository)
    {
        $this->container = $container;
        $this->original_columns_repository = $original_columns_repository;
    }

    public function create(TableScreen $table_screen): ColumnFactories
    {
        $collection = new AC\Collection\ColumnFactories();

        if ( ! $table_screen instanceof AC\TableScreen\Post || $table_screen->get_post_type()->equals('shop_order')) {
            return $collection;
        }

        $mapping = [
            'order_number'              => Original\OrderNumber::class,
            'order_date'                => Original\OrderDate::class,
            'order_status'              => Original\OrderStatus::class,
            'billing_address'           => Original\BillingAddress::class,
            'shipping_address'          => Original\ShippingAddress::class,
            'order_total'               => Original\Ordertotal::class,
            'wc_actions'                => Original\Actions::class,
            'subscription_relationship' => Original\SubscriptionRelationship::class,
        ];

        foreach ($this->original_columns_repository->find_all_cached($table_screen->get_id()) as $type => $label) {
            if ( ! array_key_exists($type, $mapping)) {
                continue;
            }

            $default_properties = [
                'type'  => $type,
                'label' => $label,
            ];

            $collection->add($this->container->make($mapping[$type], $default_properties));
        }

        return $collection;
    }
}