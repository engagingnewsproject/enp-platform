<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Formatter\CountLabelFormatter;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\ExtendedValue\Product\Variations;
use ACA\WC\Value\Formatter;
use ACA\WC\Value\Formatter\Product\VariationsCollection;
use ACP;

class VariationFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-variation';
    }

    public function get_label(): string
    {
        return __('Variations', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new VariationsCollection())
                     ->add(new Formatter\Product\Variation\Count())
                     ->add(
                         new CountLabelFormatter(
                             __('%d variation', 'codepress-admin-columns'),
                             __('%d variations', 'codepress-admin-columns')
                         )
                     )
                     ->add(new Formatter\Product\ExtendedValueVariationLink(new Variations()));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Product\Variation();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new VariationsCollection(),
            new Formatter\Product\Variation\Count(),
        ]);
    }

}