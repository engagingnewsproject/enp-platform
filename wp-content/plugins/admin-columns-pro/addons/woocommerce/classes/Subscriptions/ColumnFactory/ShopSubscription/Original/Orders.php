<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\Formatter\Count;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACP\Column\OriginalColumnFactory;

class Orders extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new WC\Subscriptions\Value\Formatter\OrderSubscription\RelatedOrderIds(),
            new Count(),
        ]);
    }

}