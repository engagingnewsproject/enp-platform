<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product\Original;

use AC\Setting\Config;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\OriginalColumnFactory;

class FeaturedFactory extends OriginalColumnFactory
{

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Product\Featured();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\Featured();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\Featured();
    }

}