<?php

namespace ACP\Sorting\FormatValue;

use AC\Helper;
use ACP\Sorting\FormatValue;

class ContentTotalImageSize implements FormatValue
{

    public function format_value($post_content)
    {
        $urls = array_unique(Helper\Image::create()->get_image_urls_from_string($post_content));

        $total_size = 0;

        $image_helper = Helper\Image::create();
        foreach ($urls as $url) {
            $size = $image_helper->get_local_image_size($url);

            if ($size > 0) {
                $total_size += $size;
            }
        }

        return $total_size > 0
            ? $total_size
            : false;
    }

}