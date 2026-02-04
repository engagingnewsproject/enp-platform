<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms;

class Product extends GravityForms\Field\Field implements GravityForms\Field\Container
{

    /**
     * @return GravityForms\Field\Field[]
     */
    public function get_sub_fields(): array
    {
        $sub_fields = [];

        foreach ($this->gf_field->inputs as $input) {
            $input_id = $input['id'] ?? null;

            if (null === $input_id) {
                continue;
            }
            
            $numbers = explode('.', (string)$input_id);

            $sub_field_number = $numbers[1] ?? 0;

            $sub_fields[(string)$input_id] = $sub_field_number == 3
                ? new Number($this->get_form_id(), $this->get_id(), $this->gf_field)
                : new Input($this->get_form_id(), $this->get_id(), $this->gf_field);
        }

        return $sub_fields;
    }

    public function get_sub_field($id): ?GravityForms\Field\Field
    {
        $sub_fields = $this->get_sub_fields();

        return $sub_fields[$id] ?? null;
    }

}