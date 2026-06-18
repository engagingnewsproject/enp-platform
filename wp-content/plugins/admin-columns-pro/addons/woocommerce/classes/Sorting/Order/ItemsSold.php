<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\Order;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\EmptyValues;
use ACP\Sorting\Type\Order;

class ItemsSold implements QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();

        $alias = $bindings->get_unique_alias('acsort');

        $subQuery = "
            SELECT order_id, SUM(product_qty) AS total_product_count
            FROM {$wpdb->prefix}wc_order_product_lookup
            GROUP BY order_id
        ";

        $bindings->join(
            "LEFT JOIN ($subQuery) AS $alias ON $alias.order_id = {$wpdb->prefix}wc_orders.id"
        );

        $bindings->order_by(
            SqlOrderByFactory::create(
                "$alias.total_product_count",
                (string)$order,
                [
                    'empty_values' => [EmptyValues::NULL, EmptyValues::ZERO],
                ]
            )
        );

        return $bindings;
    }

}