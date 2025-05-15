<?php

namespace ACP\Search\Middleware\Mapping;

use ACP\Search\Middleware\Mapping;

class Rule extends Mapping
{

    protected function get_properties(): array
    {
        return [
            'name'        => 'id',
            'operator'    => 'operator',
            'value'       => 'value',
            'value_type'  => 'type',
            'value_label' => 'formatted_value',
        ];
    }

}