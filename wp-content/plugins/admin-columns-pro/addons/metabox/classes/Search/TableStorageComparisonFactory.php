<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search;

use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACA\MetaBox\Search;
use ACP;
use ACP\Search\Value;

class TableStorageComparisonFactory
{

    public function create(Field\Field $field): ?ACP\Search\Comparison
    {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::AUTOCOMPLETE:
                if ( ! $field instanceof Field\Type\AutoComplete) {
                    return null;
                }

                return $field->is_ajax() || $field->is_cloneable()
                    ? null
                    : new Search\Comparison\Table\MultiSelect(
                        $field->get_table_storage_table(),
                        $field->get_id(),
                        $field->get_choices()
                    );
            case MetaboxFieldTypes::IMAGE_SELECT:
                $options = $field instanceof Field\Choices ? array_keys($field->get_choices()) : [];

                return $field instanceof Field\Multiple && $field->is_multiple()
                    ? new Search\Comparison\Table\MultiSelect(
                        $field->get_table_storage_table(),
                        $field->get_id(),
                        array_combine($options, $options)
                    )
                    : new Search\Comparison\Table\Select(
                        $field->get_table_storage_table(),
                        $field->get_id(),
                        array_combine($options, $options)
                    );
            case MetaboxFieldTypes::DATE:
            case MetaboxFieldTypes::DATETIME:
                switch ($field->get_date_format()) {
                    case 'U':
                        return new Search\Comparison\Table\Timestamp(
                            $field->get_table_storage_table(),
                            $field->get_id()
                        );
                    case 'Y-m-d H:i':
                    case 'Y-m-d H:i:s':
                        return new Search\Comparison\Table\DateIso(
                            $field->get_table_storage_table(),
                            $field->get_id()
                        );
                    default:
                        return null;
                }
            case MetaboxFieldTypes::CHECKBOX:
            case MetaboxFieldTypes::CHECKBOX_LIST:
            case MetaboxFieldTypes::RADIO:
            case MetaboxFieldTypes::SELECT:
            case MetaboxFieldTypes::SELECT_ADVANCED:
                return $field instanceof Field\Multiple && $field->is_multiple()
                    ? new Search\Comparison\Table\MultiSelect(
                        $field->get_table_storage_table(),
                        $field->get_id(),
                        $field instanceof Field\Choices ? $field->get_choices() : []
                    )
                    : new Search\Comparison\Table\Select(
                        $field->get_table_storage_table(),
                        $field->get_id(),
                        $field instanceof Field\Choices ? $field->get_choices() : []
                    );
            case MetaboxFieldTypes::NUMBER:
            case MetaboxFieldTypes::SLIDER:
            case MetaboxFieldTypes::RANGE:
                return new Search\Comparison\Table\Number(
                    $field->get_table_storage_table(),
                    $field->get_id()
                );
            case MetaboxFieldTypes::COLORPICKER:
            case MetaboxFieldTypes::EMAIL:
            case MetaboxFieldTypes::PASSWORD:
            case MetaboxFieldTypes::TEXT:
            case MetaboxFieldTypes::TEXTAREA:
            case MetaboxFieldTypes::TIME:
            case MetaboxFieldTypes::WYSIWYG:
                return new Search\Comparison\Table\Text(
                    $field->get_table_storage_table(),
                    $field->get_id()
                );
            case MetaboxFieldTypes::POST:
                if ( ! $field instanceof Field\Type\Post) {
                    return null;
                }

                return new Search\Comparison\Table\Post(
                    $field->get_table_storage_table(),
                    $field->get_id(),
                    $field->get_post_types(),
                    $field->get_query_args()
                );
            case MetaboxFieldTypes::TAXONOMY:
                return $field instanceof Field\Type\Taxonomy
                    ? new Search\Comparison\Taxonomy($field->get_taxonomies())
                    : null;

            case MetaboxFieldTypes::USER:
                return $field instanceof Field\Multiple && $field->is_multiple()
                    ? new Search\Comparison\Table\Users(
                        $field->get_table_storage_table(),
                        $field->get_id(),
                        $field instanceof Field\QueryArgs ? $field->get_query_args() : []
                    )
                    : new Search\Comparison\Table\User(
                        $field->get_table_storage_table(),
                        $field->get_id(),
                        $field instanceof Field\QueryArgs ? $field->get_query_args() : [],
                        Value::STRING
                    );
            case MetaboxFieldTypes::FILE:
            case MetaboxFieldTypes::FILE_ADVANCED:
            case MetaboxFieldTypes::FILE_INPUT:
            case MetaboxFieldTypes::VIDEO:
                return $field->is_cloneable()
                    ? null
                    : new Search\Comparison\Table\Media(
                        $field->get_table_storage_table(),
                        $field->get_id()
                    );
            case MetaboxFieldTypes::IMAGE:
            case MetaboxFieldTypes::IMAGE_ADVANCED:
            case MetaboxFieldTypes::SINGLE_IMAGE:
                return $field->is_cloneable()
                    ? null
                    : new Search\Comparison\Table\Media(
                        $field->get_table_storage_table(),
                        $field->get_id(),
                        ['image']
                    );
            default:
        }

        return null;
    }

}