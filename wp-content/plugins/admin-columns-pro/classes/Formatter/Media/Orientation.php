<?php

declare(strict_types=1);

namespace ACP\Formatter\Media;

use AC;
use AC\Type\Value;

class Orientation implements AC\Formatter
{

    public function format(Value $value)
    {
        $meta_data = get_post_meta($value->get_id(), '_wp_attachment_metadata', true);

        $width = $meta_data['width'] ?? null;
        $height = $meta_data['height'] ?? null;

        if ( ! $width || ! $height) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        if ($height === $width) {
            return $value->with_value(_x('Square', 'image orientation', 'codepress-admin-columns'));
        }

        $label = $width > $height
            ? _x('Landscape', 'image orientation', 'codepress-admin-columns')
            : _x('Portrait', 'image orientation', 'codepress-admin-columns');

        return $value->with_value($label);
    }

}