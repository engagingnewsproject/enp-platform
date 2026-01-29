<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Value\Formatter;
use ACP;

class TotalRevenue extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Total Revenue', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-subscription_revenue';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new ACA\WC\Subscriptions\Value\Formatter\OrderSubscription\TotalRevenue())
                     ->add(new Formatter\WcPrice());
    }

}