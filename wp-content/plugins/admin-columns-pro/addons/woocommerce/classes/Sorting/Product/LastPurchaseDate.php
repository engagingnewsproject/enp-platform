<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\Product;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\CastType;
use ACP\Sorting\Type\DataType;
use ACP\Sorting\Type\Order;

class LastPurchaseDate implements QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();

        $alias = $bindings->get_unique_alias('customers');

        $subquery = "
            SELECT product_id, MAX( date_created) as max_date_created
            FROM {$wpdb->prefix}wc_order_product_lookup opl
            INNER JOIN {$wpdb->prefix}wc_orders as o
            ON o.ID = opl.order_id
            WHERE o.status = 'wc-completed'
            GROUP BY product_id
        ";

        $bindings->join(
            "INNER JOIN ($subquery) as $alias ON $wpdb->posts.ID = $alias.product_id"
        );

        $bindings->order_by(
            SqlOrderByFactory::create("$alias.max_date_created", (string)$order, [
                'cast_type'    => CastType::create_from_data_type(new DataType(DataType::DATETIME))->get_value(),
                'empty_values' => [null],
            ])
        );

        return $bindings;
    }

}