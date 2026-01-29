<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\ConditionalFormat\Formatter\Product\OrderTotal;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\ConditionalFormat\FormattableConfig;

class OrderTotalFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-total_order_amount';
    }

    public function get_label(): string
    {
        return __('Total Revenue', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Product\OrderTotal())
                     ->add(new Formatter\WcPrice());
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Product\OrderTotal());
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(
            new OrderTotal(new Formatter\Product\OrderTotal())
        );
    }

}