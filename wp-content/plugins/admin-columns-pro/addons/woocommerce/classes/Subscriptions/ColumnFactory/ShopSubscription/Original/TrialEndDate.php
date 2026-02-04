<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\OriginalColumnFactory;

class TrialEndDate extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new WC\Subscriptions\Value\Formatter\OrderSubscription\SubscriptionDate('trial_end')
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new WC\Editing\ShopSubscription\Date('trial_end', '_schedule_trial_end');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return (new ACP\Search\Comparison\MetaFactory())->create_datetime_iso(
            '_schedule_trial_end',
            MetaType::create_post_meta(),
            'shop_subscription'
        );
    }

}