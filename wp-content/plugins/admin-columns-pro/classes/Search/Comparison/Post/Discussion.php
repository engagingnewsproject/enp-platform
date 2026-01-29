<?php

namespace ACP\Search\Comparison\Post;

use AC\Helper\Select\Options;
use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Operators;
use ACP\Search\Value;

class Discussion extends Comparison implements Comparison\Values
{

    public function __construct()
    {
        $operators = new Operators([Operators::EQ]);

        parent::__construct($operators);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        global $wpdb;

        switch ($value->get_value()) {
            case 'open':
                $combination = ['open', 'open'];
                break;
            case 'closed':
                $combination = ['closed', 'closed'];
                break;
            case 'pings_only':
                $combination = ['closed', 'open'];
                break;
            case 'comments_only':
                $combination = ['open', 'closed'];
                break;
            default:
                throw new \Exception('Invalid discussion status.');
        }

        $where_comments = $where = ComparisonFactory::create(
            $wpdb->posts . '.comment_status',
            Operators::EQ,
            new Value($combination[0])
        )->prepare();
        $where_ping = ComparisonFactory::create(
            $wpdb->posts . '.ping_status',
            Operators::EQ,
            new Value($combination[1])
        )->prepare();

        $where = sprintf('(%s AND %s)', $where_comments, $where_ping);

        $bindings = new Bindings();
        $bindings->where($where);

        return $bindings;
    }

    public function get_values(): Options
    {
        return Options::create_from_array([
            'open'          => __('Open'),
            'closed'        => __('Closed'),
            'pings_only'    => __('Pings only'),
            'comments_only' => __('Comments only'),
        ]);
    }

}