<?php

namespace ACP\Sorting\Model\Comment;

use ACP;
use ACP\Query\Bindings;
use ACP\Sorting\Type\Order;

class Status implements ACP\Sorting\Model\QueryBindings
{

    public function create_query_bindings(Order $order): Bindings
    {
        global $wpdb;

        $bindings = new Bindings();
        $orderby = ACP\Sorting\Model\SqlOrderByFactory::create_with_field(
            $wpdb->comments . '.comment_approved',
            array_keys($this->get_statuses()),
            (string)$order
        );

        return $bindings->order_by($orderby);
    }

    private function get_statuses(): array
    {
        return [
            'trash'        => __('Trash'),
            'post-trashed' => __('Trash'),
            'spam'         => __('Spam'),
            '1'            => __('Approved'),
            '0'            => __('Pending'),
        ];
    }

}