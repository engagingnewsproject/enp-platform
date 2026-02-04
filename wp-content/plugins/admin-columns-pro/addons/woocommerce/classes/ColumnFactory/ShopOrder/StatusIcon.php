<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Value\Formatter;
use ACP;

class StatusIcon extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-order_status_icon';
    }

    public function get_label(): string
    {
        return __('Status Icon', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\ShopOrder\StatusIcon());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ShopOrder\Status();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\PostField('post_status');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Status('shop_order');
    }

}