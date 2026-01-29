<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field;

use ACA\GravityForms;
use GF_Field;
use GFAPI;

class Field implements GravityForms\Field
{

    private int $form_id;

    private string $field_id;

    protected GF_Field $gf_field;

    public function __construct(int $form_id, string $field_id, GF_Field $gf_field)
    {
        $this->form_id = $form_id;
        $this->field_id = $field_id;
        $this->gf_field = $gf_field;
    }

    public function get_form_id(): int
    {
        return $this->form_id;
    }

    public function get_id(): string
    {
        return $this->field_id;
    }

    public function get_entry_value(int $id): string
    {
        return (string)(GFAPI::get_entry($id)[$this->field_id] ?? '');
    }

    public function is_required(): bool
    {
        return $this->gf_field->isRequired;
    }

}