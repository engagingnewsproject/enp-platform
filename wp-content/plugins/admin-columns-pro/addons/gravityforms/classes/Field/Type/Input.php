<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms\Field\Field;

class Input extends Field
{
    
    public function get_input_type(): string
    {
        switch ($this->gf_field->offsetGet('type')) {
            case 'website':
                return 'url';
            default:
                return 'text';
        }
    }

}