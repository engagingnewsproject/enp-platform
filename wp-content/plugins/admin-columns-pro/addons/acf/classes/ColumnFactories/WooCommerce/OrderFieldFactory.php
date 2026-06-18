<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories\WooCommerce;

use AC;
use AC\DI\Container;
use AC\Type\TableScreenContext;
use ACA\ACF;
use ACA\ACF\ColumnFactories\CloneFieldResolver;
use ACA\ACF\Field\Type\GroupSubField;
use ACA\ACF\FieldType;

class OrderFieldFactory
{

    private Container $container;

    private CloneFieldResolver $clone_field_resolver;

    public function __construct(Container $container, CloneFieldResolver $clone_field_resolver)
    {
        $this->container = $container;
        $this->clone_field_resolver = $clone_field_resolver;
    }

    public function create(TableScreenContext $table_context, ACF\Field $field): ?AC\Column\ColumnFactory
    {
        if ($field->is_clone()) {
            $resolved = $this->clone_field_resolver->resolve($field);

            if ( ! $resolved || $resolved->is_deferred_clone()) {
                return null;
            }

            return $this->create($table_context, $resolved);
        }

        if ($field instanceof GroupSubField) {
            return null;
        }

        $arguments = [
            'column_type'   => $field->get_hash(),
            'label'         => $field->get_label() ?: $field->get_meta_key(),
            'field'         => $field,
            'table_context' => $table_context,
        ];

        if ($field->is_deferred_clone()) {
            $arguments['column_type'] = 'acfclone__' . $arguments['column_type'];
        }

        switch ($field->get_type()) {
            case FieldType::TYPE_TAB:
            case FieldType::TYPE_MESSAGE:
                return null;
            case FieldType::TYPE_REPEATER:
                return $this->container->make(ACF\ColumnFactory\WooCommerce\RepeaterFieldFactory::class, $arguments);

            case FieldType::TYPE_BOOLEAN:
            case FieldType::TYPE_BUTTON_GROUP:
            case FieldType::TYPE_CHECKBOX:
            case FieldType::TYPE_COLOR_PICKER:
            case FieldType::TYPE_DATE_PICKER:
            case FieldType::TYPE_DATE_TIME_PICKER:
            case FieldType::TYPE_EMAIL:
            case FieldType::TYPE_FILE:
            case FieldType::TYPE_FLEXIBLE_CONTENT:
            case FieldType::TYPE_GALLERY:
            case FieldType::TYPE_GOOGLE_MAP:
            case FieldType::TYPE_IMAGE:
            case FieldType::TYPE_LINK:
            case FieldType::TYPE_NUMBER:
            case FieldType::TYPE_OEMBED:
            case FieldType::TYPE_PAGE_LINK:
            case FieldType::TYPE_PASSWORD:
            case FieldType::TYPE_POST:
            case FieldType::TYPE_RADIO:
            case FieldType::TYPE_RANGE:
            case FieldType::TYPE_RELATIONSHIP:
            case FieldType::TYPE_SELECT:
            case FieldType::TYPE_TAXONOMY:
            case FieldType::TYPE_TEXT:
            case FieldType::TYPE_TEXTAREA:
            case FieldType::TYPE_TIME_PICKER:
            case FieldType::TYPE_URL:
            case FieldType::TYPE_USER:
            case FieldType::TYPE_WYSIWYG:
                return $this->container->make(ACF\ColumnFactory\WooCommerce\FieldFactory::class, $arguments);
            default:
                return $this->container->make(ACF\ColumnFactory\Meta\Unsupported::class, $arguments);
        }
    }

}
