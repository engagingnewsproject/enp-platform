<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing\Product\Type;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class ProductTypeFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-product_type';
    }

    public function get_label(): string
    {
        return __('Type', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new Formatter\Product\ProductType($this->get_simple_product_types())
        );
    }

    private function get_simple_product_types(): array
    {
        return (array)apply_filters('acp/wc/editing/simple_product_types', ['simple', 'subscription']);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Type($this->get_simple_product_types());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\ProductType();
    }

}