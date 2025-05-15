<?php

namespace ACA\WC\Search\ProductVariation;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Operators;
use ACP\Search\Value;

class Image extends Comparison\Post\FeaturedImage
{

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        if ($operator === Operators::IS_EMPTY) {
            $operator = Operators::EQ;
            $value = new Value(0);
        }

        return parent::create_query_bindings($operator, $value);
    }

}