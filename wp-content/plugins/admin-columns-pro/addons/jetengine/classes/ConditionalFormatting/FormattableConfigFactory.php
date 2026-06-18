<?php

declare(strict_types=1);

namespace ACA\JetEngine\ConditionalFormatting;

use AC\Formatter;
use AC\FormatterCollection;
use ACA\JetEngine\Field;
use ACP;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\ConditionalFormat\Formatter\FilterHtmlFormatter;
use ACP\ConditionalFormat\Formatter\IntegerFormatter;
use ACP\ConditionalFormat\Formatter\StringFormatter;

class FormattableConfigFactory
{

    public function create(Field\Field $field, Formatter $formatter): ?FormattableConfig
    {
        switch ($field->get_type()) {
            case Field\Type\Date::TYPE:
                $value_formatter = new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                    new FormatterCollection([$formatter]),
                    $field instanceof Field\Type\Date && $field->is_timestamp() ? 'U' : 'Y-m-d'
                );

                return new FormattableConfig($value_formatter);
            case Field\Type\DateTime::TYPE:
                $value_formatter = new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                    new FormatterCollection([$formatter]),
                    $field instanceof Field\Type\DateTime && $field->is_timestamp() ? 'U' : 'Y-m-d\TH:i'
                );

                return new FormattableConfig($value_formatter);
            case Field\Type\Number::TYPE:
                return new FormattableConfig(new IntegerFormatter());
            case Field\Type\Gallery::TYPE:
            case Field\Type\ColorPicker::TYPE:
            case Field\Type\Switcher::TYPE:
                return null;
            case Field\Type\Media::TYPE:
            case Field\Type\Posts::TYPE:
            case Field\Type\Wysiwyg::TYPE:
                return new FormattableConfig(
                    new FilterHtmlFormatter(new StringFormatter())
                );
            default:
                return new FormattableConfig();
        }
    }

}