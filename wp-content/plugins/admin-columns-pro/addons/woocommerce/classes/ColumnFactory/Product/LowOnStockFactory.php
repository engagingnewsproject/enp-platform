<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Export;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class LowOnStockFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-low_on_stock';
    }

    public function get_label(): string
    {
        return __('Low on Stock', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\Product\LowOnStock());
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Product\IsLowOnStock());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\LowOnStock();
    }

}