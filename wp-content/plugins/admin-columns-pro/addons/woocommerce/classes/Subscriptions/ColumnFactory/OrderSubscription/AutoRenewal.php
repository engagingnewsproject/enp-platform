<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription;

use AC\Formatter\YesNoIcon;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Search;
use ACA\WC\Subscriptions\Value\Formatter\OrderSubscription\FlipBoolean;
use ACA\WC\Subscriptions\Value\Formatter\OrderSubscription\IsManual;
use ACP;

class AutoRenewal extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Auto Renewal', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column_wc_auto_renewal';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new IsManual())
                     ->add(new FlipBoolean())
                     ->add(new YesNoIcon());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\OrderSubscription\AutoRenewal();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OrderMeta('_requires_manual_renewal');
    }

}