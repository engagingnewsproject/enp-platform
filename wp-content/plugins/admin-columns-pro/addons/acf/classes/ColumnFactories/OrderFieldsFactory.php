<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories;

use AC;
use AC\Collection\ColumnFactories;
use AC\TableScreen;
use AC\Type\TableScreenContext;
use ACA\ACF\FieldRepository;
use ACA\WC;

class OrderFieldsFactory implements AC\ColumnFactoryCollectionFactory
{

    private FieldRepository $field_repository;

    private WooCommerce\OrderFieldFactory $order_field_factory;

    public function __construct(
        FieldRepository $field_repository,
        WooCommerce\OrderFieldFactory $order_field_factory
    ) {
        $this->field_repository = $field_repository;
        $this->order_field_factory = $order_field_factory;
    }

    public function create(TableScreen $table_screen): ColumnFactories
    {
        $collection = new AC\Collection\ColumnFactories();

        if ( ! $table_screen instanceof WC\TableScreen\Order) {
            return $collection;
        }

        $table_context = TableScreenContext::from_table_screen($table_screen);

        foreach ($this->field_repository->find_all($table_screen) as $field) {
            $factory = $this->order_field_factory->create($table_context, $field);

            if ($factory instanceof AC\Column\ColumnFactory) {
                $collection->add($factory);
            }
        }

        return $collection;
    }

}
