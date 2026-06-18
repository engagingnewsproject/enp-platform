<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\User;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\Order;

class AverageOrderValue implements QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();

        $statuses = array_map('esc_sql', wc_get_is_paid_statuses());
        $statuses_sql = "( 'wc-" . implode("','wc-", $statuses) . "' )";

        $alias = $bindings->get_unique_alias('acsort_avg_order');

        $subquery = sprintf(
            "
            SELECT customer_id, SUM(total_amount) / COUNT(*) AS avg_value
            FROM %swc_orders
            WHERE status IN %s
            GROUP BY customer_id
            ",
            $wpdb->prefix,
            $statuses_sql
        );

        $bindings->join(
            "LEFT JOIN ({$subquery}) AS {$alias} ON {$wpdb->users}.ID = {$alias}.customer_id"
        );
        $bindings->order_by(
            SqlOrderByFactory::create("{$alias}.avg_value", (string)$order)
        );

        return $bindings;
    }

}
