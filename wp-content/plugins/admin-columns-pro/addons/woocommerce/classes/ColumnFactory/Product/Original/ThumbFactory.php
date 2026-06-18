<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product\Original;

use AC\Setting\Config;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\OriginalColumnFactory;
use ACP\Search\Comparison\Post\FeaturedImage;

class ThumbFactory extends OriginalColumnFactory
{

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Post\FeaturedImage();
    }

    public function search(): FeaturedImage
    {
        return new FeaturedImage('product');
    }

}