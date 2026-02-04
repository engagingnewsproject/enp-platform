<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;

class OembedVideo implements Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(wp_oembed_get($value->get_value()));
    }

}