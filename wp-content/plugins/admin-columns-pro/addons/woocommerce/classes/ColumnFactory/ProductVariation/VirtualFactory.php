<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC;
use AC\Formatter\YesNoIcon;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class VirtualFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_virtual';

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-variation_virtual';
    }

    public function get_label(): string
    {
        return __('Virtual', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\ProductVariation\IsVirtual())
                     ->add(new YesNoIcon());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Meta(self::META_KEY));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ProductVariation\Virtual();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ProductVariation\Virtual();
    }

}