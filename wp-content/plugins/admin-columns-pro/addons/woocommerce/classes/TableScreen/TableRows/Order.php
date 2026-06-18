<?php

declare(strict_types=1);

namespace ACA\WC\TableScreen\TableRows;

use AC;
use AC\Request;

class Order extends AC\TableScreen\TableRows
{

    public function register(): void
    {
        ob_start();
        add_action('woocommerce_order_list_table_prepare_items_query_args', [$this, 'handle_request']);
    }

    public function handle_request(): void
    {
        ob_clean();
        $this->handle(new Request());
    }

}