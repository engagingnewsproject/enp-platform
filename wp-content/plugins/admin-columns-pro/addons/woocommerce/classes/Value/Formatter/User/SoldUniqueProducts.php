<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Formatter;
use AC\Type\Value;

class SoldUniqueProducts implements Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(
            $this->get_uniquely_sold_product_count($value->get_id())
        );
    }

    private function get_uniquely_sold_product_count(int $user_id): int
    {
        global $wpdb;

        $statuses = array_map('esc_sql', wc_get_is_paid_statuses());
        $statuses_sql = "( 'wc-" . implode("','wc-", $statuses) . "' )";

        $sql = $wpdb->prepare(
            "
            SELECT COUNT(wcopl.product_id)
            FROM {$wpdb->prefix}wc_orders AS wco
            LEFT JOIN {$wpdb->prefix}wc_order_product_lookup AS wcopl ON wcopl.order_id = wco.id
            WHERE wco.customer_id = %d
                AND wco.status IN $statuses_sql
        ",
            $user_id
        );

        return (int)$wpdb->get_var($sql);
    }

}