<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms;

class Radio extends GravityForms\Field\Field implements GravityForms\Field\Options
{

    public function get_options(): array
    {
        return (array)GravityForms\Utils\FormField::formatChoices($this->gf_field->choices);
    }

}