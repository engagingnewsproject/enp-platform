<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Value\Formatter\OrderSubscription\BillingPeriodLabel;
use ACP;

class BillingPeriod extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Billing Period', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column_wc_billing_period';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new ACA\WC\Subscriptions\Value\Formatter\OrderSubscription\BillingPeriod())
                     ->add(new BillingPeriodLabel());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select(
            '_billing_period',
            wcs_get_available_time_periods()
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OrderMeta('_billing_period');
    }

}