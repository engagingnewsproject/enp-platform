<?php

declare(strict_types=1);

namespace ACA\MetaBox\ConditionalFormatting;

use AC\FormatterCollection;
use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\ConditionalFormat\Formatter;
use ACP\ConditionalFormat\Formatter\FilterHtmlFormatter;
use ACP\ConditionalFormat\Formatter\IntegerFormatter;
use ACP\ConditionalFormat\Formatter\StringFormatter;

class FormattableConfigFactory
{

    public function create(Field\Field $field, FormatterCollection $base_formatters): ?FormattableConfig
    {
        switch ($field->get_type()) {
            case MetaboxFieldTypes::DATETIME:
                $date_format = $field instanceof Field\DateFormat ? $field->get_date_format() : 'Y-m-d H:i';

                return new FormattableConfig(
                    new Formatter\DateFormatter\BaseDateFormatter(
                        $base_formatters,
                        $date_format
                    )
                );

            case MetaboxFieldTypes::DATE:
                $date_format = $field instanceof Field\DateFormat ? $field->get_date_format() : 'Y-m-d';

                return new FormattableConfig(
                    new Formatter\DateFormatter\BaseDateFormatter(
                        $base_formatters,
                        $date_format
                    )
                );
            case MetaboxFieldTypes::WYSIWYG:
            case MetaboxFieldTypes::URL:
            case MetaboxFieldTypes::OEMBED:
            case MetaboxFieldTypes::POST:
            case MetaboxFieldTypes::TAXONOMY:
            case MetaboxFieldTypes::TAXONOMY_ADVANCED:
            case MetaboxFieldTypes::USER:
            case MetaboxFieldTypes::FILE:
            case MetaboxFieldTypes::FILE_ADVANCED:
            case MetaboxFieldTypes::FILE_UPLOAD:
                return new FormattableConfig(
                    new FilterHtmlFormatter(new StringFormatter())
                );
            case MetaboxFieldTypes::NUMBER:
            case MetaboxFieldTypes::RANGE:
                return new FormattableConfig(new IntegerFormatter());
            case MetaboxFieldTypes::CHECKBOX:
            case MetaboxFieldTypes::IMAGE:
            case MetaboxFieldTypes::IMAGE_ADVANCED:
            case MetaboxFieldTypes::SINGLE_IMAGE:
            case MetaboxFieldTypes::VIDEO:
                return null;
            default:
                return new FormattableConfig();
        }
    }
}