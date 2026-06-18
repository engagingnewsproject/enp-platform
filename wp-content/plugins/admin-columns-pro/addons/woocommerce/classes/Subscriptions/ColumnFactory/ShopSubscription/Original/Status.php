<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Subscriptions\Value\Formatter\OrderSubscription\SubscriptionStatus;
use ACP;
use ACP\Column\OriginalColumnFactory;

class Status extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new SubscriptionStatus()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Status('shop_subscription');
    }

}