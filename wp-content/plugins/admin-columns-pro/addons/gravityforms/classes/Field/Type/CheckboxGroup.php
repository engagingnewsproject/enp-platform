<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms;
use ACA\GravityForms\Field;

class CheckboxGroup extends GravityForms\Field\Field
    implements GravityForms\Field\Options, GravityForms\Field\Container
{

    public function get_options(): array
    {
        return (array)GravityForms\Utils\FormField::formatChoices($this->gf_field->choices);
    }

    /**
     * @return Checkbox[]
     */
    public function get_sub_fields(): array
    {
        $fields = [];

        foreach ($this->gf_field->inputs as $key => $input) {
            $fields[$input['id']] = new Checkbox(
                $this->get_form_id(),
                $this->get_id(),
                $this->gf_field,
                (string)$this->gf_field->choices[$key]['value'],
                $input['label'] ?? ''
            );
        }

        return $fields;
    }

    public function get_sub_field(string $id): ?Field
    {
        $fields = $this->get_sub_fields();

        return $fields[$id] ?? null;
    }

}