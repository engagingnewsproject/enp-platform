<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\Product;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\CastType;
use ACP\Sorting\Type\DataType;
use ACP\Sorting\Type\Order;

class DaysSinceLastSale implements QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $alias = $bindings->get_unique_alias('acsort_last_sale');

        $subquery = "
            SELECT opl.product_id, MAX(o.date_created_gmt) AS last_sale_date
            FROM {$wpdb->prefix}wc_order_product_lookup AS opl
            INNER JOIN {$wpdb->prefix}wc_orders AS o
                ON o.id = opl.order_id
            WHERE o.status = 'wc-completed'
            GROUP BY opl.product_id
        ";

        $bindings->join(
            "LEFT JOIN ({$subquery}) AS {$alias} ON {$wpdb->posts}.ID = {$alias}.product_id"
        );

        // "Days since" ASC = most recent first = date DESC, and vice versa
        $reversed = (string)$order === 'ASC' ? 'DESC' : 'ASC';

        $bindings->order_by(
            SqlOrderByFactory::create("{$alias}.last_sale_date", $reversed, [
                'cast_type'    => CastType::create_from_data_type(new DataType(DataType::DATETIME))->get_value(),
                'empty_values' => [null],
            ])
        );

        return $bindings;
    }

}
