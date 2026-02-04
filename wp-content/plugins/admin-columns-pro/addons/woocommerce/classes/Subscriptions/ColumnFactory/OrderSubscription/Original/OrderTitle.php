<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACP\Column\OriginalColumnFactory;

class OrderTitle extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new WC\Value\Formatter\Order\OrderId());
    }

}