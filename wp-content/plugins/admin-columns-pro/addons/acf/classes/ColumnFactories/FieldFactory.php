<?php

declare(strict_types=1);

namespace ACA\ACF\ColumnFactories;

use AC;
use AC\Type\TableScreenContext;
use AC\Vendor\DI\Container;
use ACA\ACF;
use ACA\ACF\Field;
use ACA\ACF\FieldType;

class FieldFactory
{

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function create(TableScreenContext $table_context, Field $field): ?AC\Column\ColumnFactory
    {
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

                return $this->container->make(ACF\ColumnFactory\Meta\RepeaterFieldFactory::class, $arguments);
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
                return $this->container->make(ACF\ColumnFactory\Meta\FieldFactory::class, $arguments);
            default:
                return $this->container->make(ACF\ColumnFactory\Meta\Unsupported::class, $arguments);
        }
    }

}