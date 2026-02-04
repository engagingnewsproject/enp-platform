<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Formatter\Composite;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\ConditionalFormat\Formatter\Product\SaleFormatter;
use ACA\WC\Editing;
use ACA\WC\Export;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\Sorting\Type\DataType;

class SaleFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_wc_average_rating';

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-product_sale';
    }

    public function get_label(): string
    {
        return __('Sale Price', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Product\OnSaleExtended());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\Price('sale');
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new Composite([
                new Formatter\Product\SalePrice(),
                new Formatter\Product\OnSaleLabel(),
            ])
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta('_sale_price', new DataType(DataType::NUMERIC));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\Sale();
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(
            new SaleFormatter()
        );
    }

}