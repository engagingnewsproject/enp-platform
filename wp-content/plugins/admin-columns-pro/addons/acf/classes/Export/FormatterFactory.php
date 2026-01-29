<?php

declare(strict_types=1);

namespace ACA\ACF\Export;

use AC\Formatter;
use AC\Formatter\Collection\Separator;
use AC\Formatter\Date\DateFormat;
use AC\Formatter\ForeignId;
use AC\Formatter\Media\AttachmentUrl;
use AC\Formatter\StringSanitizer;
use AC\FormatterCollection;
use ACA\ACF\Field;
use ACA\ACF\FieldType;
use ACA\ACF\Value\Formatter\LinkUrl;
use ACA\ACF\Value\Formatter\RelationIdCollection;

class FormatterFactory
{

    public function create(Field $field, Formatter $formatter, FormatterCollection $formatters): ?FormatterCollection
    {
        switch ($field->get_type()) {
            case FieldType::TYPE_DATE_PICKER:
                return new FormatterCollection([
                    $formatter,
                    new DateFormat('Y-m-d', 'Ymd'),
                ]);
            case FieldType::TYPE_LINK:
                return new FormatterCollection([
                    $formatter,
                    new LinkUrl(),
                ]);
            case FieldType::TYPE_GALLERY:
                return new FormatterCollection([
                        $formatter,
                        new RelationIdCollection(),
                        new AttachmentUrl(),
                        new Separator(','),
                    ]
                );

            case FieldType::TYPE_FILE:
            case FieldType::TYPE_IMAGE:
                return new FormatterCollection([
                        $formatter,
                        new ForeignId(),
                        new AttachmentUrl(),
                    ]
                );

            // Only apply base formatter
            case FieldType::TYPE_PASSWORD:
            case FieldType::TYPE_TIME_PICKER:
            case FieldType::TYPE_DATE_TIME_PICKER:
            case FieldType::TYPE_OEMBED:
            case FieldType::TYPE_NUMBER:
            case FieldType::TYPE_BOOLEAN:
            case FieldType::TYPE_COLOR_PICKER:
                return FormatterCollection::from_formatter($formatter);

            // Stripped render value
            default:
                return $formatters->with_formatter(new StringSanitizer())
                                  ->with_formatter(new Separator());
        }
    }

}