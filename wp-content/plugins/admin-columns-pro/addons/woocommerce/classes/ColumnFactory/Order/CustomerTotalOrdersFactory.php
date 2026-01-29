<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Value\Formatter;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\IntegerFormattableTrait;

class CustomerTotalOrdersFactory extends AdvancedColumnFactory
{

    use IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Number of Orders', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_customer_total_orders';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Formatter\Order\CustomerTotalOrders(),
        ]);
    }

}