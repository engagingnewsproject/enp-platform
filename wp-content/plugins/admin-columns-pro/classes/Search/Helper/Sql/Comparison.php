<?php

namespace ACP\Search\Helper\Sql;

use ACP\Search\Value;

class Comparison extends Statement
{

    protected string $column;

    protected string $operator;

    public function __construct(string $column, string $operator, Value $value)
    {
        $this->column = $column;
        $this->operator = $operator;

        $this->bind_value($value);

        parent::__construct($this->get_statement());
    }

    protected function get_statement(): string
    {
        return sprintf(
            '%s %s ?',
            $this->column,
            $this->operator
        );
    }

    public function __invoke(): string
    {
        return $this->prepare();
    }

}