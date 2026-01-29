<?php

declare(strict_types=1);

namespace ACA\WC\Search\Order;

use ACA\WC\Search;
use ACP;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class UserAgent extends ACP\Search\Comparison
{

    public function __construct()
    {
        parent::__construct(
            new Operators([
                Operators::CONTAINS,
                Operators::NOT_CONTAINS,
            ])
        );
    }

    protected function create_query_bindings(string $operator, Value $value): ACP\Query\Bindings
    {
        global $wpdb;

        $bindings = new ACP\Query\Bindings();
        $order_table = $wpdb->prefix . 'wc_orders';
        $field = sprintf('%s.%s', $order_table, 'user_agent');

        return $bindings->where(ComparisonFactory::create($field, $operator, $value)());
    }

}