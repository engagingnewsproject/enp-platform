<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Editing;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Value\Formatter\ProductSubscription\SubscriptionLength;
use ACP;

class Expires extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Expire after', 'woocommerce-subscriptions');
    }

    public function get_column_type(): string
    {
        return 'column-wc-subscription-expires';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new SubscriptionLength()
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta('_subscription_length');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ProductSubscription\Expires();
    }

}