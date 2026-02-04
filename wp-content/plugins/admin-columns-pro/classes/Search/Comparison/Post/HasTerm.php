<?php

namespace ACP\Search\Comparison\Post;

use ACP\Query\Bindings;
use ACP\Search\Operators;
use ACP\Search\Value;

class HasTerm extends Taxonomy
{

    protected int $term_id;

    public function __construct(string $taxonomy, int $term_id)
    {
        $this->term_id = $term_id;

        parent::__construct($taxonomy);
    }

    public function get_operators(): Operators
    {
        return new Operators([
            Operators::NOT_IS_EMPTY,
        ]);
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        $value = new Value(
            $this->term_id,
            $value->get_type()
        );

        return parent::create_query_bindings(
            Operators::IS_EMPTY === $operator ? Operators::NEQ : Operators::EQ,
            new Value(
                $this->term_id,
                $value->get_type()
            )
        );
    }

}