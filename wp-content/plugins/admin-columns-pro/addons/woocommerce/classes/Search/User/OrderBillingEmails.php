<?php

declare(strict_types=1);

namespace ACA\WC\Search\User;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class OrderBillingEmails extends Comparison
{

    public function __construct()
    {
        parent::__construct(new Operators([
            Operators::EQ,
            Operators::CONTAINS,
        ]), Value::STRING);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();

        $order_alias = $bindings->get_unique_alias('wco');

        $comparison = ComparisonFactory::create(
            "{$order_alias}.billing_email",
            $operator,
            $value
        );

        $bindings->join(
            "
            INNER JOIN {$wpdb->prefix}wc_orders AS {$order_alias}
                ON {$order_alias}.customer_id = {$wpdb->users}.ID
        "
        );

        $bindings->where(
            $comparison()
        );

        return $bindings;
    }
}