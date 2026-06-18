<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP\ColumnFactory;

class MenuOrder extends ColumnFactory\Post\Order
{

    public function get_label(): string
    {
        return __('Menu Order');
    }

}