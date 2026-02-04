<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value;

use AC;
use AC\FormatterCollection;
use ACA\JetEngine\Field;
use ACA\JetEngine\Field\Type;
use ACA\JetEngine\Value;

class ValueFormatterFactory
{

    public function create(Field\Field $field, FormatterCollection $formatters): FormatterCollection
    {
        switch ($field->get_type()) {
            case Type\Checkbox::TYPE:
                if ($field instanceof Type\Checkbox) {
                    $formatters->add(new Value\Formatter\Checkbox($field));
                }

                break;
            case Type\ColorPicker::TYPE:
                $formatters->add(new AC\Formatter\Color());

                break;
            case Type\Switcher::TYPE:
                $formatters->add(new AC\Formatter\YesNoIcon());

                break;
            case Type\Gallery::TYPE:
                $format = $field instanceof Field\ValueFormat
                    ? $field->get_value_format()
                    : null;

                $formatters->prepend(new Value\Formatter\GalleryIds($format));
                $formatters->add(new AC\Formatter\Collection\Separator(''));

                break;
            case Type\Date::TYPE:
            case Type\DateTime::TYPE:
                $formatters->prepend(new AC\Formatter\Date\Timestamp());

                break;
            case Type\Media::TYPE:
                $formatters->add(new Value\Formatter\Media($field));

                break;
            case Type\Posts::TYPE:
                $formatters->prepend(new Value\Formatter\PostIds());

                break;

            case Type\Radio::TYPE:
                $options = $field instanceof Field\Options ? $field->get_options() : [];
                $formatters->add(new Value\Formatter\Options($options));

                break;
            case Type\Select::TYPE:
                $options = $field instanceof Field\Options ? $field->get_options() : [];
                if ($field instanceof Field\Multiple && $field->is_multiple()) {
                    $formatters->add(new Value\Formatter\MultipleOptions($options));
                } else {
                    $formatters->add(new Value\Formatter\Options($options));
                }

                break;
        }

        return $formatters;
    }

}