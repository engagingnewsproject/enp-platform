<?php

namespace ACP\Search\Helper\TaxQuery;

use ACP\Search\Value;

class Comparison
{

    protected string $taxonomy;

    protected string $operator;

    private Value $terms;

    private string $field;

    public function __construct(string $taxonomy, string $operator, Value $terms, string $field = 'term_id')
    {
        $this->taxonomy = $taxonomy;
        $this->operator = $operator;
        $this->terms = $terms;
        $this->field = $field;
    }

    /**
     * @return array
     */
    public function get_expression()
    {
        return [
            'taxonomy' => $this->taxonomy,
            'terms'    => [(int)$this->terms->get_value()],
            'operator' => $this->operator,
            'field'    => $this->field,
        ];
    }

}