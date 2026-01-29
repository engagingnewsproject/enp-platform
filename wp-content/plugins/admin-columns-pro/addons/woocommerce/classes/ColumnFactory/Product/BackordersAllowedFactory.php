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
use ACP\ConditionalFormat\FormattableConfig;
use ACP\ConditionalFormat\Formatter\FormatCollectionFormatter;

class BackordersAllowedFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-backorders_allowed';
    }

    public function get_label(): string
    {
        return __('Backorders Allowed', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Product\Backorders())
                     ->add(new Formatter\Product\BackordersFormatted());
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            FormatCollectionFormatter::create([new Formatter\Product\Backorders()]),
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Product\Backorders());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\BackordersAllowed();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\BackordersAllowed();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Product\BackordersAllowed();
    }

}