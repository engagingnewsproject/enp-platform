<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\ConditionalFormat\Formatter\PriceFormatter;
use ACA\WC\Value\Formatter;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat;

class CustomerTotalSalesFactory extends AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Customer Total Sales', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_customer_total_sales';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Formatter\Order\CustomerTotalSales(),
        ]);
    }

    protected function get_conditional_format(Config $config): ?ConditionalFormat\FormattableConfig
    {
        return new ConditionalFormat\FormattableConfig(new PriceFormatter());
    }

}