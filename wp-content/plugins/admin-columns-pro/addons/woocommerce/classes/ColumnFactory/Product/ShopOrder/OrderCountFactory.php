<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class OrderCountFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-order_count';
    }

    public function get_label(): string
    {
        return __('Orders', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new Formatter\Product\ShopOrder\OrderCount());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\ShopOrder\OrderCount();
    }

}