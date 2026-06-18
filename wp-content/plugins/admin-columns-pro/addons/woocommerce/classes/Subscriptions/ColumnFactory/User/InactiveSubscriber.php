<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\User;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Value\Formatter\UserSubscription\InactiveSubscriptions;
use ACP;

class InactiveSubscriber extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Inactive subscriber', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-user_subscription_inactive';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new InactiveSubscriptions()
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\UserSubscription\InactiveSubscriber();
    }

}