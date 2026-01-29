<?php

declare(strict_types=1);

namespace ACP\Sorting\Model\Taxonomy;

use ACP\Query\Bindings;
use ACP\Sorting\Model\QueryBindings;
use ACP\Sorting\Model\SqlOrderByFactory;
use ACP\Sorting\Type\Order;

class Field implements QueryBindings
{

    private string $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    public function create_query_bindings(Order $order): Bindings
    {
        $bindings = new Bindings();

        $bindings->order_by(
            SqlOrderByFactory::create(
                sprintf("t.%s", $this->field),
                (string)$order
            )
        );

        return $bindings;
    }

}