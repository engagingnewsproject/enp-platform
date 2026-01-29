<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Export\Post;

use AC\Formatter;
use AC\Type\Value;

class Title implements Formatter
{

    public function format(Value $value)
    {
        $id = $value->get_id();
        $title = get_post_meta($id, '_yoast_wpseo_title', true);

        return $title ?: get_the_title($id);
    }

}