<?php

declare(strict_types=1);

namespace ACA\WC\Helper;

final class User
{

    public function get_shop_order_totals_for_user(int $user_id, array $status = []): array
    {
        $totals = [];

        foreach ($this->get_shop_orders_by_user($user_id, $status) as $order) {
            if ( ! $order->get_total()) {
                continue;
            }

            $currency = $order->get_currency();

            if ( ! isset($totals[$currency])) {
                $totals[$currency] = 0;
            }

            $totals[$currency] += $order->get_total();
        }

        return $totals;
    }

    public function get_shop_order_ids_by_user(int $user_id, array $status): array
    {
        $args = [
            'fields'         => 'ids',
            'post_type'      => 'shop_order',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_customer_user',
                    'value' => $user_id,
                ],
            ],
        ];

        if ($status) {
            $args['post_status'] = $status;
        }

        $order_ids = get_posts($args);

        if ( ! $order_ids) {
            return [];
        }

        return $order_ids;
    }

    public function get_shop_orders_by_user(int $user_id, array $status = ['wc-completed', 'wc-processing']): array
    {
        $orders = [];

        foreach ($this->get_shop_order_ids_by_user($user_id, $status) as $order_id) {
            $orders[] = wc_get_order($order_id);
        }

        return $orders;
    }

}