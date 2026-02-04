<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class DownloadsFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-product_downloads';
    }

    public function get_label(): string
    {
        return __('Downloads', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Product\Downloads());
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Product\DownloadFiles());
    }
}