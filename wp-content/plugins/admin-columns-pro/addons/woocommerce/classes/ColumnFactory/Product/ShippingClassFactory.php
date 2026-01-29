<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Formatter\Term\TermProperty;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter\Product\ShippingClassId;
use ACP;

class ShippingClassFactory extends ACP\Column\AdvancedColumnFactory
{

    private const TAXONOMY = 'product_shipping_class';

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-shipping_class';
    }

    public function get_label(): string
    {
        return __('Shipping Class', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new ShippingClassId())
                     ->add(new TermProperty('name'));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\ShippingClass();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Taxonomy(self::TAXONOMY);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Taxonomy(self::TAXONOMY);
    }

}