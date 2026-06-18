<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\TableScreen;

use AC;
use AC\TableScreen\ListTable;
use AC\Type\Labels;
use AC\Type\TableId;
use ACA\WC\ListTable\Orders;
use ACA\WC\Type\OrderSubscriptionTableUrl;
use Automattic;

class OrderSubscription extends AC\TableScreen implements ListTable, AC\TableScreen\TotalItems
{

    public function __construct()
    {
        parent::__construct(
            new TableId('wc_order_subscription'),
            'woocommerce_page_wc-orders--shop_subscription',
            new Labels(
                __('Subscription', 'codepress-admin-columns'),
                __('Subscriptions', 'codepress-admin-columns')
            ),
            new OrderSubscriptionTableUrl()
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