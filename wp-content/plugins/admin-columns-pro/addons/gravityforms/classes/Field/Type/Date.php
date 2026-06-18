<?php

declare(strict_types=1);

namespace ACA\GravityForms\Field\Type;

use ACA\GravityForms;

class Date extends GravityForms\Field\Field
{

    public function get_stored_date_format(): string
    {
        return 'Y-m-d';
    }
}