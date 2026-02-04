<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product\ShopOrder;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use DateTime;

class AvgOrderInterval implements Formatter
{

    private int $period_in_days;

    private ?int $start_order = null;

    public function __construct(int $period_in_days)
    {
        $this->period_in_days = $period_in_days;
    }

    public function format(Value $value)
    {
        $orders = $this->get_product_order_count($value->get_id());

        if ( ! $orders) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value(
            (int)round($this->period_in_days / $orders, 3)
        );
    }

    private function get_product_order_count($post_id): ?int
    {
        global $wpdb;

        $start_order = $this->get_start_order();

        if ( ! $start_order) {
            return null;
        }

        $num_orders = $wpdb->get_var(
            $wpdb->prepare(
                "
			SELECT COUNT( wc_oi.order_id )
			FROM {$wpdb->prefix}woocommerce_order_items wc_oi
			JOIN {$wpdb->prefix}woocommerce_order_itemmeta wc_oim
				ON wc_oi.order_item_id = wc_oim.order_item_id
			WHERE wc_oim.meta_key = '_product_id'
				AND wc_oim.meta_value = %d
				AND wc_oi.order_id > %d",
                $post_id,
                $start_order
            )
        );

        if ( ! $num_orders) {
            return null;
        }

        return (int)$num_orders;
    }

    private function get_start_order()
    {
        if (null === $this->start_order) {
            $start_date = new DateTime();
            $start_date->modify('-' . $this->period_in_days . 'days');

            $orders = get_posts([
                'post_type'      => 'shop_order',
                'post_status'    => 'completed',
                'order'          => 'ASC',
                'fields'         => 'ids',
                'date_query'     => [
                    [
                        'after'     => $start_date->format('Y-m-d'),
                        'inclusive' => true,
                    ],
                ],
                'posts_per_page' => 1,

            ]);

            $this->start_order = $orders[0] ?? 0;
        }

        return $this->start_order;
    }

}