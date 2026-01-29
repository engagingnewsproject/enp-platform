<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA;

class LinkUrl implements Formatter
{

    public function format(Value $value)
    {
        $url = $value->get_value()['url'] ?? null;

        if ( ! $url) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($url);
    }

}