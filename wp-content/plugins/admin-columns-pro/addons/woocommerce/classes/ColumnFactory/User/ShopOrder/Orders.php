<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User\ShopOrder;

use AC\Setting\Config;
use ACA\WC\ColumnFactory;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value;
use ACP;

class Orders extends ColumnFactory\User\Orders
{

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\User\ShopOrder\OrderCount($config->get('order_status', []));
    }

}