<?php

declare(strict_types=1);

namespace ACA\GravityForms\Editing\Storage\Entry;

use ACA\GravityForms\Editing\Storage;
use ACA\GravityForms\Field\Field;
use GF_Field_MultiSelect;
use GFAPI;

class MultiSelect extends Storage\Entry
{

    private Field $field;

    public function __construct(Field $field)
    {
        parent::__construct($field->get_id());

        $this->field = $field;
    }

    public function get(int $id): array
    {
        $field_id = $this->field->get_id();
        $entry_value = GFAPI::get_entry($id);

        if( is_wp_error( $entry_value ) ) {
            return [];
        }

        $value = $entry_value[$field_id] ?? '';

        return (new GF_Field_MultiSelect())->to_array($value);
    }

    public function update(int $id, $data): bool
    {
        return parent::update(
            $id,
            $data
                ? json_encode($data)
                : ''
        );
    }

}