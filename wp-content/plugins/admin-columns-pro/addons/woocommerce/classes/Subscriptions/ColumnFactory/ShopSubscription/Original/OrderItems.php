<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\OriginalColumnFactory;

class OrderItems extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        // TODO correct?
        return FormatterCollection::from_formatter(new WC\Subscriptions\Value\Formatter\OrderSubscription\Items());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\ShopOrder\Product('shop_subscription');
    }

}