<?php

namespace ACA\WC\Service;

use AC\Column;
use AC\Registerable;

class Editing implements Registerable
{

    public function register(): void
    {
        add_filter('acp/editing/post_statuses', [$this, 'remove_woocommerce_statuses_for_editing'], 10, 2);
    }

    public function remove_woocommerce_statuses_for_editing(array $statuses, Column $column): array
    {
        if (function_exists('wc_get_order_statuses') && 'shop_order' !== $column->get_post_type()) {
            $statuses = array_diff_key($statuses, wc_get_order_statuses());
        }

        return $statuses;
    }

}