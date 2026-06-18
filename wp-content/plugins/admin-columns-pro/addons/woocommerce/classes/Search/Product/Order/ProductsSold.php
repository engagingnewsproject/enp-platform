<?php

declare(strict_types=1);

namespace ACA\WC\Search\Product\Order;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;
use DateTime;

class ProductsSold extends Comparison
{

    private array $statuses;

    private ?int $number_of_days;

    public function __construct(array $statuses = ['wc-completed'], ?int $number_of_days = null)
    {
        $this->statuses = $statuses;
        $this->number_of_days = $number_of_days;

        $operators = new Operators([
            Operators::EQ,
            Operators::GT,
            Operators::GTE,
            Operators::LT,
            Operators::LTE,
            Operators::BETWEEN,
        ]);

        parent::__construct($operators, Value::INT);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $alias = $bindings->get_unique_alias('wc_opl');
        $join_alias = $bindings->get_unique_alias('product_sales');

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

        $sub_query = "
            SELECT {$alias}.product_id, SUM({$alias}.product_qty) AS total_qty
            FROM {$wpdb->prefix}wc_order_product_lookup AS {$alias}
            JOIN {$wpdb->prefix}wc_orders AS wco
                ON {$alias}.order_id = wco.ID {$status_statement} {$date_statement}
            GROUP BY {$alias}.product_id
        ";

        $comparison = ComparisonFactory::create("{$join_alias}.total_qty", $operator, $value);

        return $bindings
            ->join("INNER JOIN ({$sub_query}) AS {$join_alias} ON {$wpdb->posts}.ID = {$join_alias}.product_id")
            ->where($comparison());
    }

}
