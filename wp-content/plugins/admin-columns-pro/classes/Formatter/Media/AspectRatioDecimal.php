<?php

declare(strict_types=1);

namespace ACP\Formatter\Media;

use AC;
use AC\Type\Value;

class AspectRatioDecimal implements AC\Formatter
{

    public function format(Value $value)
    {
        $meta_data = get_post_meta($value->get_id(), '_wp_attachment_metadata', true);

        $width = $meta_data['width'] ?? null;
        $height = $meta_data['height'] ?? null;

        if ( ! $width || ! $height) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value((string)round($width / $height, 2));
    }

}