<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\User;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\CastType;
use ACP\Sorting\Type\DataType;
use ACP\Sorting\Type\Order;

class DaysSinceLastOrder implements QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $alias = $bindings->get_unique_alias('acsort_last_order');

        $subquery = sprintf(
            "
            SELECT customer_id, MAX(date_created_gmt) AS last_order_date
            FROM %swc_orders
            WHERE type = 'shop_order'
                AND status IN ('wc-completed', 'wc-processing')
            GROUP BY customer_id
            ",
            $wpdb->prefix
        );

        $bindings->join(
            "LEFT JOIN ({$subquery}) AS {$alias} ON {$wpdb->users}.ID = {$alias}.customer_id"
        );

        // "Days since" ASC = most recent first = date DESC, and vice versa
        $reversed = (string)$order === 'ASC' ? 'DESC' : 'ASC';

        $bindings->order_by(
            SqlOrderByFactory::create("{$alias}.last_order_date", $reversed, [
                'cast_type'    => CastType::create_from_data_type(new DataType(DataType::DATETIME))->get_value(),
                'empty_values' => [null],
            ])
        );

        return $bindings;
    }

}
