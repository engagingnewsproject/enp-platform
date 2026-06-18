<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\ExtendedValue\Order\Products;
use ACA\WC\Value\Formatter;
use ACP;

class PurchasedFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Products Count', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_purchased';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\Order\Purchased(new Products()));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Order\Product();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\ItemsSold();
    }

}