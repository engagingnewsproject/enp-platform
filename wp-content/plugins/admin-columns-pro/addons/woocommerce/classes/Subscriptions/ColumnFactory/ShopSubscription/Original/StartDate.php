<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\OriginalColumnFactory;

class StartDate extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new WC\Subscriptions\Value\Formatter\OrderSubscription\SubscriptionDate('start')
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new WC\Editing\ShopSubscription\Date('start', '_schedule_start');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return (new ACP\Search\Comparison\MetaFactory())->create_datetime_iso(
            '_schedule_start',
            MetaType::create_post_meta(),
            'shop_subscription'
        );
    }

}