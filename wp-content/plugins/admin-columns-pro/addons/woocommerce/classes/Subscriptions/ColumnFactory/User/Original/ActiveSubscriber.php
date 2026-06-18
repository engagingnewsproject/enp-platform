<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\User\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\OriginalColumnFactory;

class ActiveSubscriber extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new WC\Value\Formatter\User\ShopOrder\ActiveSubscriber());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\UserSubscription\ActiveSubscriber();
    }

}