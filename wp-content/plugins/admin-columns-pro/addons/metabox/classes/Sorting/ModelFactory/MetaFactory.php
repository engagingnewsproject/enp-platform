<?php

declare(strict_types=1);

namespace ACA\MetaBox\Sorting\ModelFactory;

use AC\Setting\Config;
use AC\Type\TableScreenContext;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACA\MetaBox\Sorting;
use ACP;

class MetaFactory
{

    private ACP\Sorting\Model\MetaFactory $meta_factory;

    public function __construct(ACP\Sorting\Model\MetaFactory $meta_factory)
    {
        $this->meta_factory = $meta_factory;
    }

    public function create(
        Field\Field $field,
        TableScreenContext $table_context,
        Config $config
    ): ?ACP\Sorting\Model\QueryBindings {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::IMAGE_SELECT:
                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    return null;
                }

                return $this->meta_factory->create($table_context->get_meta_type(), $field->get_id());
            case MetaboxFieldTypes::SELECT_ADVANCED:
            case MetaboxFieldTypes::SELECT:
            case MetaboxFieldTypes::RADIO:
                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    return null;
                }

                $options = $field instanceof Field\Choices ? $field->get_choices() : [];
                natcasesort($options);

                return (new ACP\Sorting\Model\MetaMappingFactory())->create(
                    (string)$table_context->get_meta_type(),
                    $field->get_id(),
                    array_keys($options)
                );

            case MetaboxFieldTypes::NUMBER:
            case MetaboxFieldTypes::SLIDER:
            case MetaboxFieldTypes::RANGE:
                return $this->meta_factory->create(
                    $table_context->get_meta_type(),
                    $field->get_id(),
                    new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::NUMERIC)
                );

            case MetaboxFieldTypes::DATE:
            case MetaboxFieldTypes::DATETIME:
                if ( ! $field instanceof Field\DateFormat) {
                    return null;
                }

                $data_type = $field->get_date_format() === 'U'
                    ? new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::NUMERIC)
                    : null;

                return $this->meta_factory->create($table_context->get_meta_type(), $field->get_id(), $data_type);

            case MetaboxFieldTypes::TAXONOMY_ADVANCED:
                return (new ACP\Sorting\Model\MetaFormatFactory())->create(
                    $table_context->get_meta_type(),
                    $field->get_id(),
                    new Sorting\FormatValue\Taxonomy(),
                    null,
                    [
                        'post_type' => $table_context->has_post_type() ? (string)$table_context->get_post_type() : null,
                        'taxonomy'  => $table_context->has_taxonomy() ? (string)$table_context->get_taxonomy() : null,
                    ]
                );
            case MetaboxFieldTypes::USER:
                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    return null;
                }

                return (new ACP\Sorting\Model\RelatedMetaUserFactory())->create(
                    $table_context->get_meta_type(),
                    (string)$config->get('display_author_as', ''),
                    $field->get_id()
                );
            case MetaboxFieldTypes::POST:
                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    return null;
                }

                return (new ACP\Sorting\Model\RelatedMetaPostFactory())->create(
                    $table_context->get_meta_type(),
                    (string)$config->get('post', ''),
                    $field->get_id()
                );
            default:
                return $this->meta_factory->create($table_context->get_meta_type(), $field->get_id());
        }
    }
}