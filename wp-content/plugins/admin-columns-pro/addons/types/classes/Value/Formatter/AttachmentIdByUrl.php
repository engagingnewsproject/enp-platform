<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class AttachmentIdByUrl implements Formatter
{

    public function format(Value $value): Value
    {
        $url = $value->get_value();

        if ( ! $url) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $upload_dir = wp_get_upload_dir();

        $image = get_posts([
            'post_type'      => 'attachment',
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => '_wp_attached_file',
                    'value' => ltrim(str_replace($upload_dir['baseurl'], '', $url), '/'),
                ],
            ],
            'posts_per_page' => 1,
        ]);

        if ( ! $image) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return new Value($image[0]);
    }
}