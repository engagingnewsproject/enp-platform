<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\ConditionalFormat\Formatter\PriceFormatter;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\ConditionalFormat\FormattableConfig;

class AverageOrderValueFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Average Order Value', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-avg_order_value';
    }

    public function get_description(): ?string
    {
        return __("Customer's total revenue divided by their number of orders.", 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\User\AverageOrderValue())
                     ->add(new Formatter\WcPrice());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\User\AverageOrderValue();
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(new PriceFormatter());
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\User\AverageOrderValue());
    }

}
