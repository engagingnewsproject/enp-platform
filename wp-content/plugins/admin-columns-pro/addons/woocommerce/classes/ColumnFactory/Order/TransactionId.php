<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class TransactionId extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Transaction ID', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-order_transaction_id';
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Order\TransactionId();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OrderData('transaction_id');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new Formatter\Order\TransactionId());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Text())->set_clear_button(true),
            new ACA\WC\Editing\Storage\Order\TransactionId()
        );
    }

}