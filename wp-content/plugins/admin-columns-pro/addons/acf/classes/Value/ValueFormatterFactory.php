<?php

declare(strict_types=1);

namespace ACA\ACF\Value;

use AC;
use AC\Formatter\Collection\Separator;
use AC\Formatter\Post\PostLink;
use AC\Formatter\Post\PostTitle;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\ACF\Field;
use ACA\ACF\FieldType;
use ACA\ACF\Value;

class ValueFormatterFactory
{

    public function add_field_formatters(FormatterCollection $formatters, Field $field, Config $config): void
    {
        switch ($field->get_type()) {
            case FieldType::TYPE_COLOR_PICKER:
                $formatters->add(new AC\Formatter\Color());
                break;
            case FieldType::TYPE_BOOLEAN:
                $formatters->add(new AC\Formatter\YesNoIcon());
                break;
            case FieldType::TYPE_SELECT:
            case FieldType::TYPE_RADIO:
            case FieldType::TYPE_CHECKBOX:
            case FieldType::TYPE_BUTTON_GROUP:
                $formatters
                    ->add(new Value\Formatter\Choice($field instanceof Field\Choices ? $field->get_choices() : []))
                    ->add(Separator::create_from_config($config));
                break;
            case FieldType::TYPE_LINK:
                $formatters->add(new Value\Formatter\Link());
                break;
            case FieldType::TYPE_GOOGLE_MAP:
                $formatters->add(new Value\Formatter\Maps());
                break;
            case FieldType::TYPE_DATE_PICKER:
                $formatters->prepend(new Value\Formatter\AcfDate());
                break;
            case FieldType::TYPE_DATE_TIME_PICKER:
                $formatters->prepend(new AC\Formatter\Date\Timestamp());
                break;
            case FieldType::TYPE_TIME_PICKER:
                $formatters->prepend(new AC\Formatter\Date\TimeFormat());
                break;
            case FieldType::TYPE_FLEXIBLE_CONTENT:
                if ($field instanceof Field\Type\FlexibleContent) {
                    $formatters->prepend(
                        $config->get('flex_display') === 'structure'
                            ? new Value\Formatter\FlexStructure($field)
                            : new Value\Formatter\FlexCount($field)
                    );
                }
                break;
            case FieldType::TYPE_POST:
            case FieldType::TYPE_RELATIONSHIP:
            case FieldType::TYPE_TAXONOMY:
            case FieldType::TYPE_USER:
                $formatters
                    ->prepend(new Value\Formatter\RelationIdCollection())
                    ->add(Separator::create_from_config($config));
                break;
            case FieldType::TYPE_GALLERY:
                $formatters
                    ->prepend(new Value\Formatter\RelationIdCollection())
                    ->add(new Separator('', (int)$config->get('number_of_items', 10)));
                break;
            case FieldType::TYPE_PAGE_LINK:
                $formatters->prepend(new Value\Formatter\PageLink(new PostTitle(), new PostLink('edit_post')));
                break;
            case FieldType::TYPE_WYSIWYG:
                $formatters->add(new AC\Formatter\StripTags());
                break;
            case FieldType::TYPE_FILE:
                $is_multiple = $field instanceof Field\Multiple && $field->is_multiple();

                if ($is_multiple) {
                    $formatters->prepend(new Value\Formatter\RelationIdCollection());
                }

                if ($config->get('file_display') === 'preview') {
                    $size = $config->get('file_preview_size') ?: 'thumbnail';

                    if ($size === 'cpac-custom') {
                        $size = [
                            $config->has('file_preview_w') ? (int)$config->get('file_preview_w') : 80,
                            $config->has('file_preview_h') ? (int)$config->get('file_preview_h') : 80,
                        ];
                    }

                    $formatters->add(new Value\Formatter\File($size));
                } else {
                    $formatters->add(new Value\Formatter\FileLink((string)$config->get('file_link_to', '')));
                }

                if ($is_multiple) {
                    $formatters->add(Separator::create_from_config($config));
                }
                break;
        }
    }

}