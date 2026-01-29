<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;

class Ip extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;
    use ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-wc-order_ip';
    }

    public function get_label(): string
    {
        return __('Customer IP address', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\Order\CustomerIpAddress());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Text())->set_clear_button(true),
            new ACP\Editing\Storage\Meta('_customer_ip_address', new MetaType(MetaType::POST))
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta('_customer_ip_address');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text('_customer_ip_address');
    }

}