<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\Order;

use ACA\WC\Scheme\OrderOperationalData;
use ACA\WC\Scheme\Orders;
use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\Order;

class FulfillmentTime implements QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();

        $table_orders = $wpdb->prefix . Orders::TABLE;
        $table_od = $wpdb->prefix . OrderOperationalData::TABLE;

        $alias = $bindings->get_unique_alias('acsort_fulfillment');

        $bindings->join(
            sprintf(
                "LEFT JOIN %s AS %s ON %s.order_id = %s.id",
                esc_sql($table_od),
                $alias,
                $alias,
                esc_sql($table_orders)
            )
        );

        $bindings->order_by(
            SqlOrderByFactory::create(
                sprintf(
                    "DATEDIFF(%s.date_completed_gmt, %s.date_created_gmt)",
                    $alias,
                    esc_sql($table_orders)
                ),
                (string)$order,
                [
                    'empty_values' => [null],
                    'esc_sql'      => false,
                ]
            )
        );

        return $bindings;
    }

}
