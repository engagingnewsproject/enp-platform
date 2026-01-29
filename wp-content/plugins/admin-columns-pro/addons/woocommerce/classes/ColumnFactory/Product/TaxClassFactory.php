<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Helper\Tax;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class TaxClassFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_tax_class';

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-tax_class';
    }

    public function get_label(): string
    {
        return __('Tax Class', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Product\TaxClass());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\TaxClass();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\TaxClass(
            (new Tax())->get_tax_class_options()
        );
    }

}