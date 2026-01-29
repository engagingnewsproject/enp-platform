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
use InvalidArgumentException;

class ValueFormatterFactory
{

    public function get_field_formatters(
        FormatterCollection $formatters,
        Field $field,
        Config $config
    ): FormatterCollection {
        switch ($field->get_type()) {
            case FieldType::TYPE_COLOR_PICKER:
                return $formatters->add(new AC\Formatter\Color());

            case FieldType::TYPE_BOOLEAN:
                return $formatters->add(new AC\Formatter\YesNoIcon());
            case FieldType::TYPE_SELECT:
            case FieldType::TYPE_RADIO:
            case FieldType::TYPE_CHECKBOX:
            case FieldType::TYPE_BUTTON_GROUP:
                return $formatters
                    ->add(new Value\Formatter\Choice($field instanceof Field\Choices ? $field->get_choices() : []))
                    ->add(Separator::create_from_config($config));
            case FieldType::TYPE_LINK:
                return $formatters->add(new Value\Formatter\Link());
            case FieldType::TYPE_GOOGLE_MAP:
                return $formatters->add(new Value\Formatter\Maps());
            case FieldType::TYPE_DATE_PICKER:
                return $formatters->prepend(new Value\Formatter\AcfDate());
            case FieldType::TYPE_DATE_TIME_PICKER:
                return $formatters->prepend(new AC\Formatter\Date\Timestamp());
            case FieldType::TYPE_FILE:
                return $formatters->prepend(new Value\Formatter\File());
            case FieldType::TYPE_FLEXIBLE_CONTENT:
                if ( ! $field instanceof Field\Type\FlexibleContent) {
                    throw new InvalidArgumentException('Field must be instance of Field\Type\FlexibleContent');
                }

                $formatter = $config->get('flex_display') === 'structure'
                    ? new Value\Formatter\FlexStructure($field)
                    : new Value\Formatter\FlexCount($field);

                return $formatters->prepend($formatter);

            case FieldType::TYPE_POST:
            case FieldType::TYPE_RELATIONSHIP:
            case FieldType::TYPE_TAXONOMY:
            case FieldType::TYPE_USER:
                return $formatters->prepend(new Value\Formatter\RelationIdCollection())
                                  ->add(Separator::create_from_config($config));
            case FieldType::TYPE_GALLERY:
                return $formatters->prepend(new Value\Formatter\RelationIdCollection())
                                  ->add(new Separator('', (int)$config->get('number_of_items', 10)));
            case FieldType::TYPE_PAGE_LINK:
                return $formatters->prepend(new Value\Formatter\PageLink(new PostTitle(), new PostLink('edit_post')));
            case FieldType::TYPE_WYSIWYG:
                return $formatters->add(new AC\Formatter\StripTags());
            default:
                return $formatters;
        }
    }

}