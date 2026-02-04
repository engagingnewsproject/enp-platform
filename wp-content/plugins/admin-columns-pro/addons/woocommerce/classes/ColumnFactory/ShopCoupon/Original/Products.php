<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon\Original;

use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Export;
use ACA\WC\Search;
use ACA\WC\Value\Formatter\ShopCoupon\IncludedProductCollection;
use ACP;
use ACP\Column\OriginalColumnFactory;

class Products extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new IncludedProductCollection(),
            new Separator(),
        ]);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ShopCoupon\Products('product_ids');
    }

}