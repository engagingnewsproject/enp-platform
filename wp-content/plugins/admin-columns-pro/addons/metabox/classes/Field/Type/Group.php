<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;
use ACA\MetaBox\FieldFactory;

class Group extends Field\Field
{

    /**
     * @return Field\Field[]
     */
    public function get_sub_fields(): array
    {
        $field_factory = new FieldFactory();
        $fields = [];

        foreach ($this->settings['fields'] as $sub_field) {
            $field = $field_factory->create($sub_field);
            if ($field instanceof Field\Field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

}