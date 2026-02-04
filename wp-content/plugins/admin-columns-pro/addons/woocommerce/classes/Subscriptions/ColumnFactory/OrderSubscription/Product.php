<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription;

use AC\Setting\Config;
use ACA\WC;
use ACP;

class Product extends WC\ColumnFactory\Order\ProductFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Subscriptions\Search\OrderSubscription\Product();
    }

}