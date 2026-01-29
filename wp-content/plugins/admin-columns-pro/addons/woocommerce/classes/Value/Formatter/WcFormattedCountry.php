<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class WcFormattedCountry implements Formatter
{

    public function format(Value $value)
    {
        $countries = WC()->countries->get_countries();

        if ( ! isset($countries[$value->get_value()])) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($countries[$value->get_value()]);
    }

}