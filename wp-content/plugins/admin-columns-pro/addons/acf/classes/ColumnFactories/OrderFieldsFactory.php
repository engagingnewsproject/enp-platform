<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories;

use AC;
use AC\Collection\ColumnFactories;
use AC\TableScreen;
use ACA\ACF;
use ACA\ACF\FieldRepository;
use ACA\WC;

class OrderFieldsFactory implements AC\ColumnFactoryCollectionFactory
{

    private FieldRepository $field_repository;

    private WooCommerce\OrderFieldFactory $order_field_factory;

    private WooCommerce\OrderCloneFieldFactory $clone_field_factory;

    public function __construct(
        FieldRepository $field_repository,
        WooCommerce\OrderFieldFactory $order_field_factory,
        WooCommerce\OrderCloneFieldFactory $order_clone_field_factory
    ) {
        $this->field_repository = $field_repository;
        $this->order_field_factory = $order_field_factory;
        $this->clone_field_factory = $order_clone_field_factory;
    }

    public function create(TableScreen $table_screen): ColumnFactories
    {
        $collection = new AC\Collection\ColumnFactories();

        if ( ! $table_screen instanceof WC\TableScreen\Order) {
            return $collection;
        }

        /**
         * @var $field ACF\Field
         */
        foreach ($this->field_repository->find_all($table_screen) as $field) {
            switch (true) {
                case $field->is_clone():
                    $factory = $this->clone_field_factory->create($field);
                    break;
                case $field instanceof ACF\Field\Type\GroupSubField:
                    $factory = null;
                    break;

                default:
                    $factory = $this->order_field_factory->create($field);
            }

            if ($factory instanceof AC\Column\ColumnFactory) {
                $collection->add($factory);
            }
        }

        return $collection;
    }

}