<?php

declare(strict_types=1);

namespace ACA\Pods\Editing;

use ACA\Pods\Editing;
use ACA\Pods\Field;
use ACA\Pods\FieldTypes;

final class StorageFactory
{

    public function create_by_field(Field $field)
    {
        switch ($field->get_type()) {
            case FieldTypes::DATE:
            case FieldTypes::DATETIME:
                return new Editing\Storage\Date(
                    $field,
                    new Editing\Storage\Read\PodsRaw($field->get_pod()->get_name(), $field->get_name()),
                    $field->get_field()->get_arg('date_format', 'Y-m-d')
                );
            default:
                return new Editing\Storage\Field(
                    $field,
                    new Editing\Storage\Read\PodsRaw($field->get_pod()->get_name(), $field->get_name())
                );
        }
    }

}