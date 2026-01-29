<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACA\WC\Subscriptions;
use ACP;
use ACP\Column\OriginalColumnFactory;

class Status extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new Subscriptions\Value\Formatter\OrderSubscription\SubscriptionStatus()
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select(wcs_get_subscription_statuses()),
            new WC\Editing\Storage\OrderSubscription\Status()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\Order\Status(wcs_get_subscription_statuses());
    }

}