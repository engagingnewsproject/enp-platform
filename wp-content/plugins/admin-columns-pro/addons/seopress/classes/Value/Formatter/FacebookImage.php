<?php

declare(strict_types=1);

namespace ACA\SeoPress\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;

class FacebookImage implements Formatter
{

    public function format(Value $value)
    {
        $image_value = get_post_meta($value->get_id(), '_seopress_social_fb_img_attachment_id', true);

        if ( ! $image_value) {
            $image_value = get_post_meta($value->get_id(), '_seopress_social_fb_img', true);
        }

        return $value->with_value($image_value);
    }

}