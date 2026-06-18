<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class OembedVideo implements Formatter
{

    public function format(Value $value)
    {
        $embed = wp_oembed_get($value->get_value());

        if (false === $embed) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($embed);
    }

}