<?php

declare(strict_types=1);

namespace ACA\WC\TableScreen;

use AC;
use AC\TableScreen;
use AC\TableScreen\ListTable;
use AC\Type\Labels;
use AC\Type\TableId;
use ACA\WC\ListTable\Orders;
use ACA\WC\Type\OrderTableUrl;
use Automattic;

class Order extends TableScreen implements ListTable, TableScreen\TotalItems
{

    public function __construct()
    {
        parent::__construct(
            new TableId('wc_order'),
            'woocommerce_page_wc-orders',
            new Labels(
                __('Order', 'woocommerce'),
                __('Orders', 'woocommerce')
            ),
            new OrderTableUrl()
        );
    }

    public function get_total_items(): int
    {
        $table = wc_get_container()->get(Automattic\WooCommerce\Internal\Admin\Orders\ListTable::class);

        return $table instanceof Automattic\WooCommerce\Internal\Admin\Orders\ListTable
            ? (int)$table->get_pagination_arg('total_items')
            : 0;
    }

    public function list_table(): AC\ListTable
    {
        return new Orders(
            wc_get_container()->get(Automattic\WooCommerce\Internal\Admin\Orders\ListTable::class)
        );
    }

}