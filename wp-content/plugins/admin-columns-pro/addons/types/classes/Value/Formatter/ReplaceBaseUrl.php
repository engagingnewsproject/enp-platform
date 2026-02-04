<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;

class ReplaceBaseUrl implements Formatter
{

    public function format(Value $value)
    {
        $label = $value->get_value();

        $base_url = wp_upload_dir()['baseurl'] ?? null;

        if ($base_url) {
            $label = str_replace($base_url, '', $label);
        }

        return $value->with_value(
            ac_helper()->html->link($value->get_value(), $label)
        );
    }
}