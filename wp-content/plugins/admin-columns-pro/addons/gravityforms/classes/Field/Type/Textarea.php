<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms\Field\Field;

class Textarea extends Field
{
    
    public function get_input_type(): string
    {
        return 'textarea';
    }

}