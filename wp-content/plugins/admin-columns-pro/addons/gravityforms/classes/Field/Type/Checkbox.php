<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms;
use GF_Field;

class Checkbox extends GravityForms\Field\Field
{

    private string $value;

    private string $label;

    public function __construct(int $form_id, string $field_id, GF_Field $field, string $value, string $label)
    {
        parent::__construct($form_id, $field_id, $field);

        $this->value = $value;
        $this->label = $label;
    }

    public function get_value(): string
    {
        return $this->value;
    }

    public function get_label(): string
    {
        return $this->label;
    }

}