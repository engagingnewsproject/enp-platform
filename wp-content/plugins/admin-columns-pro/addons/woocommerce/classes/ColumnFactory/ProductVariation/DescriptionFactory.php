<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;

class DescriptionFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_variation_description';

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-product_description';
    }

    public function get_label(): string
    {
        return __('Description', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Meta('_variation_description'));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ProductVariation\Description();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

}