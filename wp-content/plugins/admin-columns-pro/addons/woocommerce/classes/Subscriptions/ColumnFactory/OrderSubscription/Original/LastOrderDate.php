<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Subscriptions;
use ACP\Column\OriginalColumnFactory;

class LastOrderDate extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new Subscriptions\Value\Formatter\OrderSubscription\SubscriptionDate('last_order_date_created')
        );
    }

}