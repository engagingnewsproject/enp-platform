<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms;
use ACA\GravityForms\Field;
use GF_Field;

class Select extends GravityForms\Field\Field implements Field\Options, Field\Multiple
{

    private array $choices;

    private bool $multiple;

    public function __construct(int $form_id, string $field_id, GF_Field $gf_field, array $choices, bool $multiple)
    {
        parent::__construct($form_id, $field_id, $gf_field);

        $this->choices = $choices;
        $this->multiple = $multiple;
    }

    public function get_options(): array
    {
        return $this->choices;
    }

    public function is_multiple(): bool
    {
        return $this->multiple;
    }

}