<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Formatter;

use AC;
use AC\FormatterCollection;
use ACA\GravityForms\Export;
use ACA\GravityForms\Export\Formatter\Entry\EntryProperty;
use ACA\GravityForms\Field;

class EntryFactory
{

    public function create(Field\Field $field): ?FormatterCollection
    {
        switch (true) {
            case $field instanceof Field\Type\Address:
                return new FormatterCollection([
                    new EntryProperty($field->get_id()),
                    (new AC\Formatter\PregReplace())->replace_br('; '),
                    new AC\Formatter\StripTags(),
                ]);

            case $field instanceof Field\Type\Checkbox:
            case $field instanceof Field\Type\Consent:
                return new FormatterCollection([
                    new EntryProperty($field->get_id()),
                    new AC\Formatter\HasValue(),
                ]);

            case $field instanceof Field\Type\Product:
            case $field instanceof Field\Type\ItemList:
                return new FormatterCollection([
                    new EntryProperty($field->get_id()),
                    new Export\Formatter\Entry\ItemList(),
                ]);

            case $field instanceof Field\Type\Number:
                return new FormatterCollection([
                    new EntryProperty($field->get_id()),
                    new Export\Formatter\Entry\Number(),
                ]);

            default:
                return new FormatterCollection([
                    new EntryProperty($field->get_id()),
                    (new AC\Formatter\PregReplace())->replace_br(' '),
                    new AC\Formatter\StripTags(),
                ]);
        }
    }

}