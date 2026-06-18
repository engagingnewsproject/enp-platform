<?php

declare(strict_types=1);

namespace ACA\WC\Service;

use AC\Column\Context;
use AC\Registerable;

class Editing implements Registerable
{

    public function register(): void
    {
        add_filter('ac/editing/post_statuses', [$this, 'remove_woocommerce_statuses_for_editing'], 10, 3);
    }

    public function remove_woocommerce_statuses_for_editing(array $statuses, Context $context, string $post_type): array
    {
        if (function_exists('wc_get_order_statuses') && 'shop_order' !== $post_type) {
            $statuses = array_diff_key($statuses, wc_get_order_statuses());
        }

        return $statuses;
    }

}