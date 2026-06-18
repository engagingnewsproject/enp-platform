<?php

declare(strict_types=1);

namespace ACA\ACF\Sorting;

use AC\Setting\Config;
use AC\Type\TableScreenContext;
use ACA\ACF\Field;
use ACA\ACF\FieldType;
use ACA\ACF\Sorting;
use ACP;

class ModelFactory
{

    private ModelFactory\Relation $relation_factory;

    public function __construct(ModelFactory\Relation $relation_factory)
    {
        $this->relation_factory = $relation_factory;
    }

    public function create(
        Field $field,
        string $meta_key,
        TableScreenContext $table_context,
        Config $config
    ): ?ACP\Sorting\Model\QueryBindings {
        $meta_type = $table_context->get_meta_type();

        switch ($field->get_type()) {
            case FieldType::TYPE_NUMBER:
            case FieldType::TYPE_RANGE:
                return (new ACP\Sorting\Model\MetaFactory())->create(
                    $meta_type,
                    $meta_key,
                    new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::DECIMAL)
                );

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
                return (new ACP\Sorting\Model\MetaFactory())->create($meta_type, $meta_key);

            case FieldType::TYPE_DATE_PICKER:
                return (new ACP\Sorting\Model\MetaFactory())->create(
                    $meta_type,
                    $meta_key,
                    new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::NUMERIC)
                );

            case FieldType::TYPE_DATE_TIME_PICKER:
                return (new ACP\Sorting\Model\MetaFactory())->create(
                    $meta_type,
                    $meta_key,
                    new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::DATETIME)
                );

            case FieldType::TYPE_CHECKBOX:
                $choices = $field instanceof Field\Choices ? $field->get_choices() : [];

                return (new ACP\Sorting\Model\MetaFormatFactory())->create(
                    $meta_type,
                    $meta_key,
                    new Sorting\FormatValue\Select($choices),
                    null,
                    [
                        'post_type' => $table_context->has_post_type() ? (string)$table_context->get_post_type() : null,
                        'taxonomy'  => $table_context->has_taxonomy() ? (string)$table_context->get_taxonomy() : null,
                    ]
                );
            case FieldType::TYPE_FILE:
                return (new ACP\Sorting\Model\MetaFormatFactory())->create(
                    $meta_type,
                    $meta_key,
                    new Sorting\FormatValue\File(),
                    null,
                    [
                        'post_type' => $table_context->has_post_type() ? (string)$table_context->get_post_type() : null,
                        'taxonomy'  => $table_context->has_taxonomy() ? (string)$table_context->get_taxonomy() : null,
                    ]
                );

            case FieldType::TYPE_RADIO:
            case FieldType::TYPE_BUTTON_GROUP:
                $choices = $field instanceof Field\Choices ? $field->get_choices() : [];
                natcasesort($choices);

                return (new ACP\Sorting\Model\MetaMappingFactory())->create(
                    (string)$meta_type,
                    $meta_key,
                    array_keys($choices)
                );

            case FieldType::TYPE_SELECT:
                $choices = $field instanceof Field\Choices ? $field->get_choices() : [];
                natcasesort($choices);

                return $field instanceof Field\Multiple && $field->is_multiple()
                    ? (new ACP\Sorting\Model\MetaFormatFactory())->create(
                        $meta_type,
                        $meta_key,
                        new Sorting\FormatValue\Select($choices),
                        null,
                        [
                            'post_type' => $table_context->has_post_type()
                                ? (string)$table_context->get_post_type()
                                : null,
                            'taxonomy'  => $table_context->has_taxonomy()
                                ? (string)$table_context->get_taxonomy()
                                : null,
                        ]
                    )
                    : (new ACP\Sorting\Model\MetaMappingFactory())->create(
                        (string)$meta_type,
                        $meta_key,
                        array_keys($choices)
                    );

            case FieldType::TYPE_RELATIONSHIP:
            case FieldType::TYPE_POST:
            case FieldType::TYPE_PAGE_LINK:
                return $this->relation_factory->create($field, $meta_key, $table_context, $config);

            case FieldType::TYPE_USER:
                return (new Sorting\ModelFactory\User())->create($field, $meta_key, $table_context, $config);

            case FieldType::TYPE_TAXONOMY:
                return (new Sorting\ModelFactory\Taxonomy())->create($field, $meta_key, $table_context);

            default:
                return null;
        }
    }

}