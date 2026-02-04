<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class LinkableUrlDecode implements AC\Formatter
{

    public function format(Value $value)
    {
        $url = $value->get_value();

        if ( ! is_string($url)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            ac_helper()->html->link($url, urldecode(str_replace(['http://', 'https://'], '', $url)))
        );
    }

}