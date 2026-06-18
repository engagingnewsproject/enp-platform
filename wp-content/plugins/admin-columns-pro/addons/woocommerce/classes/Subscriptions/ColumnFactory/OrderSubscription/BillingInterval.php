<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Value\Formatter\OrderSubscription\BillingIntervalLabel;
use ACP;

class BillingInterval extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Billing Interval', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column_wc_billing_interval';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new ACA\WC\Subscriptions\Value\Formatter\OrderSubscription\BillingInterval())
                     ->add(new BillingIntervalLabel());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select(
            '_billing_interval',
            wcs_get_subscription_period_interval_strings()
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OrderMeta(
            '_billing_interval',
            new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::NUMERIC)
        );
    }

}