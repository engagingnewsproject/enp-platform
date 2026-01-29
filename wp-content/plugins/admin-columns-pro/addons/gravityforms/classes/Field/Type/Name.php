<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms;
use ACA\GravityForms\Field;

class Name extends GravityForms\Field\Field implements GravityForms\Field\Container
{

    public function get_sub_fields(): array
    {
        $sub_fields = [];

        foreach ($this->gf_field->inputs as $input) {
            $sub_fields[$input['id']] = isset($input['inputType']) && 'radio' === $input['inputType']
                ? new Select(
                    $this->get_form_id(),
                    $this->get_id(),
                    $this->gf_field,
                    GravityForms\Utils\FormField::formatChoices($input['choices']),
                    false
                )
                : new Input($this->get_form_id(), $this->get_id(), $this->gf_field);
        }

        return $sub_fields;
    }

    public function get_sub_field(string $id): ?Field
    {
        $sub_fields = $this->get_sub_fields();

        return $sub_fields[$id] ?? null;
    }

}