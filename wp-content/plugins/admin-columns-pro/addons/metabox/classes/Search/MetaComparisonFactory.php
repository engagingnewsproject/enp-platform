<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search;

use AC\Meta\Query;
use AC\Meta\QueryMetaFactory;
use AC\Type\TableScreenContext;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACA\MetaBox\Search;
use ACP;

class MetaComparisonFactory
{

    protected function create_query(TableScreenContext $table_context, string $meta_key): Query
    {
        $factory = new QueryMetaFactory();

        return $table_context->has_post_type()
            ? $factory->create_with_post_type($meta_key, (string)$table_context->get_post_type())
            : $factory->create($meta_key, $table_context->get_meta_type());
    }

    public function create(Field\Field $field, TableScreenContext $table_context): ?ACP\Search\Comparison
    {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::AUTOCOMPLETE:
                if ( ! $field instanceof Field\Type\AutoComplete) {
                    return null;
                }

                return $field->is_ajax() || $field->is_cloneable()
                    ? null
                    : new ACP\Search\Comparison\Meta\Select(
                        $field->get_id(),
                        $field->get_choices()
                    );
            case MetaboxFieldTypes::CHECKBOX:
                return new ACP\Search\Comparison\Meta\Checkmark($field->get_id());
            case MetaboxFieldTypes::DATE:
            case MetaboxFieldTypes::DATETIME:
                if ( ! $field instanceof Field\DateFormat) {
                    return null;
                }

                switch ($field->get_date_format()) {
                    case 'U':
                        return new ACP\Search\Comparison\Meta\DateTime\Timestamp(
                            $field->get_id(),
                            $this->create_query($table_context, $field->get_id())
                        );
                    case 'Y-m-d':
                        return new ACP\Search\Comparison\Meta\Date(
                            $field->get_id(),
                            $this->create_query($table_context, $field->get_id())
                        );
                    case 'Y-m-d H:i':
                        return new ACP\Search\Comparison\Meta\DateTime\ISO(
                            $field->get_id(),
                            $this->create_query($table_context, $field->get_id())
                        );
                    default:
                        return null;
                }

            case MetaboxFieldTypes::IMAGE_SELECT:
                $options = $field instanceof Field\Choices ? array_keys($field->get_choices()) : [];

                return $field->is_cloneable() || empty($options)
                    ? null
                    : new ACP\Search\Comparison\Meta\Select(
                        $field->get_id(),
                        array_combine($options, $options)
                    );
            case MetaboxFieldTypes::RADIO:
            case MetaboxFieldTypes::SELECT_ADVANCED:
            case MetaboxFieldTypes::SELECT:
            case MetaboxFieldTypes::CHECKBOX_LIST:
                return $field->is_cloneable()
                    ? null
                    : new ACP\Search\Comparison\Meta\Select(
                        $field->get_id(),
                        $field instanceof Field\Choices ? $field->get_choices() : []
                    );

            case MetaboxFieldTypes::NUMBER:
            case MetaboxFieldTypes::SLIDER:
            case MetaboxFieldTypes::RANGE:
                return $field->is_cloneable()
                    ? new ACP\Search\Comparison\Meta\Serialized($field->get_id())
                    : new ACP\Search\Comparison\Meta\Number($field->get_id());

            case MetaboxFieldTypes::EMAIL:
            case MetaboxFieldTypes::COLORPICKER:
            case MetaboxFieldTypes::PASSWORD:
            case MetaboxFieldTypes::TEXT:
            case MetaboxFieldTypes::URL:
            case MetaboxFieldTypes::OEMBED:
            case MetaboxFieldTypes::TEXTAREA:
            case MetaboxFieldTypes::TIME:
            case MetaboxFieldTypes::WYSIWYG:
                return $field->is_cloneable()
                    ? new ACP\Search\Comparison\Meta\Serialized($field->get_id())
                    : new ACP\Search\Comparison\Meta\Text($field->get_id());
            case MetaboxFieldTypes::POST:
                if ( ! $field instanceof Field\Type\Post || $field->is_cloneable()) {
                    return null;
                }

                return new ACP\Search\Comparison\Meta\Post(
                    $field->get_id(),
                    $field->get_post_types()
                );
            case MetaboxFieldTypes::TAXONOMY:
                return $field instanceof Field\Type\Taxonomy
                    ? new Search\Comparison\Taxonomy($field->get_taxonomies())
                    : null;
            case MetaboxFieldTypes::USER:
                return $field->is_cloneable()
                    ? null
                    : new ACP\Search\Comparison\Meta\User(
                        $field->get_id(),
                        $this->create_query($table_context, $field->get_id())
                    );
            case MetaboxFieldTypes::FILE:
            case MetaboxFieldTypes::FILE_ADVANCED:
            case MetaboxFieldTypes::FILE_UPLOAD:
                return $field->is_cloneable()
                    ? new ACP\Search\Comparison\Meta\Serialized($field->get_id())
                    : new ACP\Search\Comparison\Meta\Media(
                        $field->get_id(),
                        $this->create_query($table_context, $field->get_id())
                    );
            case MetaboxFieldTypes::VIDEO:
                return $field->is_cloneable()
                    ? null
                    : new ACP\Search\Comparison\Meta\Attachment(
                        $field->get_id(),
                        $this->create_query($table_context, $field->get_id()),
                        'video'
                    );
            case MetaboxFieldTypes::IMAGE:
            case MetaboxFieldTypes::IMAGE_ADVANCED:
            case MetaboxFieldTypes::SINGLE_IMAGE:
                return new ACP\Search\Comparison\Meta\Image(
                    $field->get_id(),
                    $this->create_query($table_context, $field->get_id())
                );
        }

        return null;
    }

}