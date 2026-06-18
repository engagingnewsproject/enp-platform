<?php

namespace ACP\ApplyFilter\CustomField;

use AC\Column\Context;

class StoredDateFormat
{

    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function apply_filters(string $date_format): string
    {
        return (string)apply_filters('ac/custom_field/stored_date_format', $date_format, $this->context);
    }

}