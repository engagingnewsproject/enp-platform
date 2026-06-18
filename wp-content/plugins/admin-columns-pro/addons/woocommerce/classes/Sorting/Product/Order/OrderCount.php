<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\Product\Order;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\Order;

class OrderCount implements QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $alias = $bindings->get_unique_alias('acsort_order_count');

        $subquery = "
            SELECT wcopl.product_id, COUNT(*) AS total_count
            FROM {$wpdb->prefix}wc_order_product_lookup AS wcopl
            JOIN {$wpdb->prefix}wc_orders AS wco
                ON wcopl.order_id = wco.ID
            GROUP BY wcopl.product_id
        ";

        $bindings->join(
            "LEFT JOIN ({$subquery}) AS {$alias} ON {$wpdb->posts}.ID = {$alias}.product_id"
        );
        $bindings->order_by(
            SqlOrderByFactory::create("{$alias}.total_count", (string)$order)
        );

        return $bindings;
    }

}
