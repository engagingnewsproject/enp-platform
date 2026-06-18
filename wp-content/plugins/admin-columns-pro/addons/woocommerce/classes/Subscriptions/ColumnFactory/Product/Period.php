<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\Product;

use AC;
use AC\Formatter\Aggregate;
use AC\Formatter\Composite;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Editing;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Search;
use ACP;

class Period extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Price Period', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-subscription-period';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        $interval = Aggregate::from_array([
            new AC\Formatter\Post\Meta('_subscription_period_interval'),
            new AC\Formatter\MapOptionLabel(wcs_get_subscription_period_interval_strings()),
        ]);

        $period = Aggregate::from_array([
            new AC\Formatter\Post\Meta('_subscription_period'),
            new AC\Formatter\MapOptionLabel(wcs_get_subscription_period_strings()),
        ]);

        $formatters->add(
            new Composite([
                $interval,
                $period,
            ])
        );

        return $formatters;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ProductSubscription\Period();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ProductSubscription\Period();
    }

}