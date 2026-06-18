<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class ProductDetailFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Product Details', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-product_details';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\Order\ProductDetails());
    }

}