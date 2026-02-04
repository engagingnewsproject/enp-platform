<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Value\Formatter;
use ACP;

class TransactionId extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;
    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-wc-transaction_id';
    }

    public function get_label(): string
    {
        return __('Transaction ID', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Order\TransactionId());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta('_transaction_id');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text('_transaction_id');
    }

}