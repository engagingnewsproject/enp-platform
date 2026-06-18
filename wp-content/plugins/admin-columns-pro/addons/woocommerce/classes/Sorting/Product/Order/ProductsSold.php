<?php

declare(strict_types=1);

namespace ACA\WC\Sorting\Product\Order;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\Order;
use DateTime;

class ProductsSold implements QueryBindings
{

    private array $statuses;

    private ?int $number_of_days;

    public function __construct(array $statuses = ['wc-completed'], ?int $number_of_days = null)
    {
        $this->statuses = $statuses;
        $this->number_of_days = $number_of_days;
    }

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $alias = $bindings->get_unique_alias('acsort_sales');

        $status_statement = '';

        if ( ! empty($this->statuses)) {
            $status_statement = sprintf(
                "AND wco.status IN ('%s')",
                implode("','", array_map('esc_sql', $this->statuses))
            );
        }

        $date_statement = '';

        if ($this->number_of_days !== null) {
            $start_date = new DateTime();
            $start_date->modify('-' . $this->number_of_days . ' days');

            $date_statement = sprintf(
                "AND wco.date_created_gmt >= '%s'",
                $start_date->format('Y-m-d')
            );
        }

        $subquery = "
            SELECT wcopl.product_id, SUM(wcopl.product_qty) AS total_qty
            FROM {$wpdb->prefix}wc_order_product_lookup AS wcopl
            JOIN {$wpdb->prefix}wc_orders AS wco
                ON wcopl.order_id = wco.ID {$status_statement} {$date_statement}
            GROUP BY wcopl.product_id
        ";

        $bindings->join(
            "LEFT JOIN ({$subquery}) AS {$alias} ON {$wpdb->posts}.ID = {$alias}.product_id"
        );
        $bindings->order_by(
            SqlOrderByFactory::create("{$alias}.total_qty", (string)$order)
        );

        return $bindings;
    }

}
