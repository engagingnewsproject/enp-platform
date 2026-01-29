<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order\Original;

use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\OriginalColumnFactory;

class TotalFactory extends OriginalColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\Order\Total();
    }

}