<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\Formatter\Id;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;

class OrderIdFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('ID');
    }

    public function get_column_type(): string
    {
        return 'column-order_id';
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Order\OrderId();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OrderBy('id');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Id(),
        ]);
    }

}