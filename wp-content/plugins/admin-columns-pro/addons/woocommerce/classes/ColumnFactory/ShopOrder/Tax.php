<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\ConditionalFormat\Formatter\ShopOrder\TaxFormatter;
use ACA\WC\Value\Formatter;
use ACP;

class Tax extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-order_tax';
    }

    public function get_label(): string
    {
        return __('Tax', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\Order\TaxTotals());
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new TaxFormatter()
        );
    }

}