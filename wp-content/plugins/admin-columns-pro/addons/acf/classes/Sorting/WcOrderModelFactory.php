<?php

declare(strict_types=1);

namespace ACA\ACF\Sorting;

use ACA\ACF\Field;
use ACA\ACF\FieldType;
use ACA\WC;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Type\DataType;

final class WcOrderModelFactory
{

    public function create(Field $field): ?QueryBindings
    {
        switch ($field->get_type()) {
            case FieldType::TYPE_NUMBER:
            case FieldType::TYPE_RANGE:
                return new WC\Sorting\Order\OrderMeta($field->get_meta_key(), DataType::create_decimal());
            case FieldType::TYPE_DATE_PICKER:
                return new WC\Sorting\Order\OrderMeta($field->get_meta_key(), DataType::create_numeric());
            case FieldType::TYPE_TEXT:
            case FieldType::TYPE_TEXTAREA:
            case FieldType::TYPE_WYSIWYG:
            case FieldType::TYPE_EMAIL:
            case FieldType::TYPE_COLOR_PICKER:
            case FieldType::TYPE_OEMBED:
            case FieldType::TYPE_URL:
            case FieldType::TYPE_PASSWORD:
            case FieldType::TYPE_TIME_PICKER:
            case FieldType::TYPE_IMAGE:
            case FieldType::TYPE_BOOLEAN:
                return new WC\Sorting\Order\OrderMeta($field->get_meta_key());
            case FieldType::TYPE_DATE_TIME_PICKER:
                return new WC\Sorting\Order\OrderMeta($field->get_meta_key(), new DataType(DataType::DATETIME));
            case FieldType::TYPE_RADIO:
            case FieldType::TYPE_BUTTON_GROUP:
                $choices = $field instanceof Field\Choices ? $field->get_choices() : [];
                natcasesort($choices);

                return new WC\Sorting\Order\OrderMetaMapping($field->get_meta_key(), array_keys($choices));
            case FieldType::TYPE_SELECT:
                $choices = $field instanceof Field\Choices ? $field->get_choices() : [];
                natcasesort($choices);

                return $field instanceof Field\Multiple && $field->is_multiple()
                    ? null
                    : new WC\Sorting\Order\OrderMetaMapping($field->get_meta_key(), array_keys($choices));
        }

        return null;
    }

}