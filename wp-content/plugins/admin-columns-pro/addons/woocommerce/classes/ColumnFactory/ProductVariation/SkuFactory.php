<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class SkuFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'variation_sku';
    }

    public function get_label(): string
    {
        return __('SKU', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\ProductVariation\Sku());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $view = (new ACP\Editing\View\Text())->set_clear_button(true);

        return new ACP\Editing\Service\Basic(
            $view,
            new Editing\Storage\Product\Sku()
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Meta('_sku'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ProductVariation\SKU();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\ProductVariation\SkuNaturalSort();
    }

}