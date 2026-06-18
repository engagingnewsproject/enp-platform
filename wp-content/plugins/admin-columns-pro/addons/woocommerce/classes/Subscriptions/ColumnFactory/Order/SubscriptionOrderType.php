<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\Value\Formatter;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;

class SubscriptionOrderType extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;

    public function get_label(): string
    {
        return __('Subscription Order Type', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column_wc_subscription_order_type';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\Order\OrderType());
    }

}