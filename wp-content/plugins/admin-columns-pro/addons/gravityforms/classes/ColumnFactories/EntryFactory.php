<?php

declare(strict_types=1);

namespace ACA\GravityForms\ColumnFactories;

use AC\Collection;
use AC\Collection\ColumnFactories;
use AC\ColumnFactoryCollectionFactory;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA\GravityForms;
use ACA\GravityForms\ColumnFactory;
use ACA\GravityForms\FieldFactory;

class EntryFactory implements ColumnFactoryCollectionFactory
{

    private Container $container;

    private OriginalColumnsRepository $original_columns_repository;

    private FieldFactory $field_factory;

    public function __construct(
        Container $container,
        OriginalColumnsRepository $original_columns_repository,
        FieldFactory $field_factory
    ) {
        $this->container = $container;
        $this->original_columns_repository = $original_columns_repository;
        $this->field_factory = $field_factory;
    }

    public function create(TableScreen $table_screen): ColumnFactories
    {
        $collection = new Collection\ColumnFactories();

        if ( ! $table_screen instanceof GravityForms\TableScreen\Entry) {
            return $collection;
        }

        $form_id = $table_screen->get_form_id();

        $default_mapping = [
            'is_starred'              => ColumnFactory\Entry\StarredFactory::class,
            'field_id-created_by'     => ColumnFactory\Entry\CreatedByFactory::class,
            'field_id-date_created'   => ColumnFactory\Entry\DateCreatedFactory::class,
            'field_id-id'             => ColumnFactory\Entry\IdFactory::class,
            'field_id-ip'             => ColumnFactory\Entry\IpFactory::class,
            'field_id-payment_amount' => ColumnFactory\Entry\PaymentAmountFactory::class,
            'field_id-payment_date'   => ColumnFactory\Entry\DatePaymentFactory::class,
            'field_id-payment_status' => ColumnFactory\Entry\PaymentStatusFactory::class,
            'field_id-source_url'     => ColumnFactory\Entry\SourceUrlFactory::class,
            'field_id-transaction_id' => ColumnFactory\Entry\TransactionIdFactory::class,
        ];

        foreach ($this->original_columns_repository->find_all_cached($table_screen->get_id()) as $column) {
            $column_type = $column->get_name();
            $field = $this->field_factory->create_by_column_type($column_type, $form_id);

            if ($field) {
                $collection->add(
                    $this->container->make(
                        GravityForms\ColumnFactory\EntryFactory::class,
                        [
                            'type'  => $column_type,
                            'label' => $column->get_label(),
                            'field' => $field,
                        ]
                    )
                );

                continue;
            }

            if (array_key_exists($column_type, $default_mapping)) {
                $collection->add(
                    $this->container->make(
                        $default_mapping[$column_type],
                        [
                            'type'  => $column_type,
                            'label' => $column->get_label(),
                        ]
                    )
                );
            }
        }

        return $collection;
    }

}