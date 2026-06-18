<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User\ShopOrder;

use AC\Setting\Config;
use ACA\WC\ColumnFactory;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value;
use ACP;

class OrderCount extends ColumnFactory\User\OrderCountFactory
{

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\User\ShopOrder\OrderCount($this->get_order_status($config));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\User\ShopOrder\OrderCount($this->get_order_status($config));
    }

}