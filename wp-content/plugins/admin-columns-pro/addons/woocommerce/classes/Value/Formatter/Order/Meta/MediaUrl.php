<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order\Meta;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class MediaUrl implements Formatter
{

    public function format(Value $value)
    {
        $metadata = $value->get_value();

        if (is_numeric($metadata)) {
            $url = wp_get_attachment_url($metadata);

            return $value->with_value($url ?: '');
        }

        if (filter_var($metadata, FILTER_VALIDATE_URL) && preg_match('/[^\w.-]/', $metadata)) {
            return $value->with_value($metadata);
        }

        throw ValueNotFoundException::from_id($value->get_id());
    }

}