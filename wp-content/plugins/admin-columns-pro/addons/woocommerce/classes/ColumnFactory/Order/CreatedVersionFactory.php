<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search\Order\CreatedVersion;
use ACA\WC\Sorting\Order\OperationalData;
use ACA\WC\Value\Formatter;
use ACP;

class CreatedVersionFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('WooCommerce Version', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_created_version';
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new CreatedVersion();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new OperationalData('woocommerce_version');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\Order\CreatedVersion());
    }

}