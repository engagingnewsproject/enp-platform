<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Formatter\MapToLabel;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class VisibilityFactory extends ACP\Column\AdvancedColumnFactory
{

    private const TAXONOMY = 'product_visibility';

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-visibility';
    }

    public function get_label(): string
    {
        return __('Catalog Visibility', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new MapToLabel(
                new Formatter\Product\CatalogVisibility(),
                wc_get_product_visibility_options()
            )
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\Visibility(wc_get_product_visibility_options());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\Visibility();
    }

}