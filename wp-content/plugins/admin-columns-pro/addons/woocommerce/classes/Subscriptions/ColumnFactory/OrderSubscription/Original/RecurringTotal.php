<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACA\WC\Subscriptions;
use ACP;
use ACP\Column\OriginalColumnFactory;

class RecurringTotal extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Subscriptions\Value\Formatter\OrderSubscription\OrderTotal());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\Order\Total();
    }

}