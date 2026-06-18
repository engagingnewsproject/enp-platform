<?php

declare(strict_types=1);

namespace ACA\MetaBox\Sorting\ModelFactory;

use AC\MetaType;
use AC\Type\TableScreenContext;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACA\MetaBox\Sorting;
use ACP;
use ACP\Sorting\Type\DataType;

class TableStorageFactory
{

    public function create(Field\Field $field, TableScreenContext $table_context): ?ACP\Sorting\Model\QueryBindings
    {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::SELECT_ADVANCED:
            case MetaboxFieldTypes::SELECT:
                return $field instanceof Field\Multiple && $field->is_multiple()
                    ? null
                    : $this->create_for_meta_type($field, $table_context);
            default:
                return $this->create_for_meta_type($field, $table_context);
        }
    }

    public function create_for_meta_type(
        Field\Field $field,
        TableScreenContext $table_context
    ): ?ACP\Sorting\Model\QueryBindings {
        $data_type = $this->create_data_type($field);

        switch ($table_context->get_meta_type()->get()) {
            case MetaType::USER:
                return new Sorting\Model\User\Table($field->get_table_storage_table(), $field->get_id(), $data_type);
            case MetaType::POST:
                return new Sorting\Model\Post\Table($field->get_table_storage_table(), $field->get_id(), $data_type);
            case MetaType::TERM:
                return new Sorting\Model\Taxonomy\Table(
                    $field->get_table_storage_table(), $field->get_id(), $data_type
                );
            default:
                return null;
        }
    }

    private function create_data_type(Field\Field $field): DataType
    {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::NUMBER:
            case MetaboxFieldTypes::SLIDER:
            case MetaboxFieldTypes::RANGE:
                return new DataType(DataType::NUMERIC);
            case MetaboxFieldTypes::DATE:
            case MetaboxFieldTypes::DATETIME:
                return $field instanceof Field\DateFormat && $field->is_timestamp()
                    ? new DataType(DataType::NUMERIC)
                    : new DataType(DataType::DATETIME);
        }

        return new DataType(DataType::STRING);
    }
}