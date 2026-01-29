<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC\Formatter\YesNoIcon;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class EnabledFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-variation_enabled';
    }

    public function get_label(): string
    {
        return __('Enabled', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\ProductVariation\HasStatus('publish'))
                     ->add(new YesNoIcon());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ProductVariation\Enabled();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ProductVariation\Enabled();
    }

}