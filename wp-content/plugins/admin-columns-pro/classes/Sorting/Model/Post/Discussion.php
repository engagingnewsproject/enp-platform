<?php

declare(strict_types=1);

namespace ACP\Sorting\Model\Post;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\Order;

class Discussion implements QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $bindings->order_by(
            SqlOrderByFactory::create(
                "CONCAT( $wpdb->posts.comment_status , $wpdb->posts.ping_status )",
                (string)$order
            )
        );

        return $bindings;
    }

}