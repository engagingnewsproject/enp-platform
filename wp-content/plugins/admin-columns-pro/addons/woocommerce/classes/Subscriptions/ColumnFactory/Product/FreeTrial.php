<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Value\Formatter\ProductSubscription\TrialLabel;
use ACP;

class FreeTrial extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Free trial', 'woocommerce-subscriptions');
    }

    public function get_column_type(): string
    {
        return 'column-wc-subscription-free_trial';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new TrialLabel()
        );
    }

}