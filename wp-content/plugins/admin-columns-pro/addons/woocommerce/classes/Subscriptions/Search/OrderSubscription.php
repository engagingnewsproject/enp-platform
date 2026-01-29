<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Search;

use ACP\Search;

class OrderSubscription extends Search\TableMarkup
{

    public function register(): void
    {
        parent::register();

        add_action(
            'woocommerce_order_list_table_restrict_manage_orders',
            [$this, 'filters_markup'],
            1
        );
    }

}