<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\Product;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Type\Order;

class StockValue implements QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();

        $alias = $bindings->get_unique_alias('acsort_pml');
        $alias_var = $bindings->get_unique_alias('acsort_var');

        $bindings->join(
            sprintf(
                "LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup AS {$alias} ON {$alias}.product_id = {$wpdb->posts}.ID
                LEFT JOIN (
                    SELECT p.post_parent AS parent_id,
                           SUM(CAST(pml.min_price AS DECIMAL(10,2)) * CAST(pml.stock_quantity AS SIGNED)) AS total_stock_value
                    FROM {$wpdb->posts} AS p
                    INNER JOIN {$wpdb->prefix}wc_product_meta_lookup AS pml ON pml.product_id = p.ID
                    WHERE p.post_type = 'product_variation'
                      AND p.post_parent > 0
                      AND pml.stock_quantity > 0
                      AND pml.min_price > 0
                    GROUP BY p.post_parent
                ) AS %s ON %s.parent_id = {$wpdb->posts}.ID",
                $alias_var,
                $alias_var
            )
        );

        $expression = sprintf(
            "COALESCE(%s.total_stock_value, CAST(%s.min_price AS DECIMAL(10,2)) * CAST(%s.stock_quantity AS SIGNED))",
            $alias_var,
            $alias,
            $alias
        );

        $bindings->order_by(
            sprintf(
                "CASE WHEN %s IS NULL OR %s <= 0 THEN 1 ELSE 0 END, %s %s",
                $expression,
                $expression,
                $expression,
                (string)$order
            )
        );

        return $bindings;
    }

}
