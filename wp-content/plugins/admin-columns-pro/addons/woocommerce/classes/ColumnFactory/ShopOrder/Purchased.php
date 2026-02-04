<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\ExtendedValue;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Search\Comparison;
use ACP\Sorting\Model\QueryBindings;

class Purchased extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;
    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    public function get_column_type(): string
    {
        return 'column-wc-purchased';
    }

    public function get_label(): string
    {
        return sprintf(
            '%s / %s',
            __('Products', 'codepress-admin-columns'),
            __('Purchased', 'codepress-admin-columns')
        );
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new Formatter\Order\Purchased(new ExtendedValue\Order\Products())
        );
    }

    protected function get_sorting(Config $config): ?QueryBindings
    {
        return new Sorting\ShopOrder\ItemCount();
    }

    protected function get_search(Config $config): ?Comparison
    {
        return new Search\ShopOrder\ProductCount();
    }

}