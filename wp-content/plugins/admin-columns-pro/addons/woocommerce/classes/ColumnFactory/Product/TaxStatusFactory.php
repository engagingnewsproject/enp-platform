<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class TaxStatusFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_tax_status';

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-tax_status';
    }

    public function get_label(): string
    {
        return __('Tax Status', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\Product\TaxStatus($this->get_tax_status()));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\TaxStatus($this->get_tax_status());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\TaxStatus($this->get_tax_status());
    }

    private function get_tax_status(): array
    {
        return [
            'taxable'  => __('Taxable', 'woocommerce'),
            'shipping' => __('Shipping only', 'woocommerce'),
            'none'     => _x('None', 'Tax status', 'woocommerce'),
        ];
    }

}