<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

class Email extends Input
{

    public function get_input_type(): string
    {
        return 'email';
    }

}