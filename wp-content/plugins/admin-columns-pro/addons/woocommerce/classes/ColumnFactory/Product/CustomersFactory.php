<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Formatter\CountLabelFormatter;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\ConditionalFormat;

class CustomersFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-product_customers';
    }

    public function get_label(): string
    {
        return __('Customers', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Product\CustomerCount())
                     ->add(
                         new CountLabelFormatter(
                             __('%d customer', 'codepress-admin-columns'),
                             __('%d customers', 'codepress-admin-columns')
                         )
                     )
                     ->add(new Formatter\Product\Customers());
    }

    protected function get_conditional_format(Config $config): ?ConditionalFormat\FormattableConfig
    {
        return new ConditionalFormat\FormattableConfig(
            new ConditionalFormat\Formatter\FilterHtmlFormatter(
                new ConditionalFormat\Formatter\IntegerFormatter()
            )
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Product\Order\Customers();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Product\CustomerCount());
    }

}