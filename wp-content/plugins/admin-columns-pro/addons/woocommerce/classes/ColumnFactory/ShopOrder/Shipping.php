<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;

class Shipping extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;
    use ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-wc-order_shipping';
    }

    public function get_label(): string
    {
        return __('Shipping Costs', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\Order\ShippingTotal());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Decimal('_order_shipping');
    }

}