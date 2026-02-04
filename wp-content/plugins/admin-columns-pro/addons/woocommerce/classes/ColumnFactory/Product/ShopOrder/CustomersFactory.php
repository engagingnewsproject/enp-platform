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

class CustomersFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
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
        return parent::get_formatters($config)->prepend(new Formatter\Product\ShopOrder\Customers());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Product\ShopOrder\Customers();
    }

}