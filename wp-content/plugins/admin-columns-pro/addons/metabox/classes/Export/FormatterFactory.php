<?php

declare(strict_types=1);

namespace ACA\MetaBox\Export;

use AC;
use AC\Formatter\StringSanitizer;
use AC\FormatterCollection;
use ACA\MetaBox;

final class FormatterFactory
{

    public function create(
        MetaBox\Field\Field $field,
        FormatterCollection $base_formatters,
        FormatterCollection $formatters
    ): ?AC\FormatterCollection {
        switch ($field->get_type()) {
            case MetaBox\MetaboxFieldTypes::CHECKBOX:
                return $base_formatters;
            case MetaBox\MetaboxFieldTypes::FILE_UPLOAD:
            case MetaBox\MetaboxFieldTypes::IMAGE:
            case MetaBox\MetaboxFieldTypes::IMAGE_ADVANCED:
                return $base_formatters->with_formatter(
                    new MetaBox\Value\Formatter\FileNames()
                );
            case MetaBox\MetaboxFieldTypes::SINGLE_IMAGE:
                return $base_formatters->with_formatter(
                    new MetaBox\Value\Formatter\ImageId(),
                )->with_formatter(
                    new AC\Formatter\Media\AttachmentUrl()
                );
            default:
                return $formatters->with_formatter(new StringSanitizer());
        }
    }

}