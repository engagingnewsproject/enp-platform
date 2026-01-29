<?php

namespace ACP\Search\Helper\Sql\Comparison;

use ACP\Search\Helper\Sql\Comparison;
use ACP\Search\Value;

class Like extends Comparison
    implements Negatable
{

    public function __construct(string $column, Value $value)
    {
        $operator = 'LIKE';

        if ($this->is_negated()) {
            $operator = 'NOT ' . $operator;
        }

        $value = new Value(
            $this->escape_value($value->get_value()),
            $value->get_type()
        );

        parent::__construct($column, $operator, $value);
    }

    public function is_negated(): bool
    {
        return false;
    }

    protected function escape_value($value): string
    {
        global $wpdb;

        return $wpdb->esc_like($value);
    }

    protected function value_begins_with(string $value): string
    {
        return $value . '%';
    }

    protected function value_ends_with(string $value): string
    {
        return '%' . $value;
    }

    protected function value_contains(string $value): string
    {
        return '%' . $value . '%';
    }

}