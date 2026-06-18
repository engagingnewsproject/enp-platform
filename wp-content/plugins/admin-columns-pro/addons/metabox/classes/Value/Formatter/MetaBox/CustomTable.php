<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter\MetaBox;

use AC;
use AC\Helper;
use AC\Type\Value;

class CustomTable implements AC\Formatter
{

    public function format(Value $value)
    {
        $data = $value->get_value();

        if ( ! isset($data['custom_table']['enable']) || ! $data['custom_table']['enable']) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            Helper\Icon::create()->yes() . ' ' . $data['custom_table']['name']
        );
    }

}