<?php

declare(strict_types=1);

namespace ACA\SeoPress\Value\Formatter;

use AC\Formatter;
use AC\Type\Value;
use AC\View;

class FacebookPreview implements Formatter
{

    public function format(Value $value)
    {
        $view = (new View([
            'image_url'   => get_post_meta($value->get_id(), '_seopress_social_fb_img', true),
            'url'         => get_permalink($value->get_id()),
            'title'       => get_post_meta($value->get_id(), '_seopress_social_fb_title', true)
                ?: get_the_title(
                    $value->get_id()
                ),
            'description' => get_post_meta($value->get_id(), '_seopress_social_fb_desc', true),
        ]))->set_template('column/seo/facebook-preview');

        return $value->with_value($view->render());
    }

}