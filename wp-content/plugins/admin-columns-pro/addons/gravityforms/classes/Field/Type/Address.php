<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms;
use ACA\GravityForms\Field;
use GF_Field_Address;

class Address extends GravityForms\Field\Field implements GravityForms\Field\Container
{

    /**
     * @return GravityForms\Field[]
     */
    public function get_sub_fields(): array
    {
        $sub_fields = [];

        foreach ($this->gf_field->offsetGet('inputs') as $input) {
            $input_id = $input['id'] ?? null;

            if (null === $input_id) {
                continue;
            }

            $input_id = (string)$input_id;
            $parts = explode('.', $input_id);
            $sub_id = (int)($parts[1] ?? 0);

            if (6 === $sub_id) {
                $countries = $this->gf_field instanceof GF_Field_Address
                    ? (array)$this->gf_field->get_countries()
                    : [];

                $sub_fields[$input_id] = new Select(
                    $this->get_form_id(),
                    $this->get_id(),
                    $this->gf_field,
                    array_combine($countries, $countries),
                    false
                );
            } else {
                $sub_fields[$input['id']] = new Input($this->get_form_id(), $this->get_id(), $this->gf_field);
            }
        }

        return $sub_fields;
    }

    public function get_sub_field(string $id): ?Field
    {
        $sub_fields = $this->get_sub_fields();

        return $sub_fields[$id] ?? null;
    }

}