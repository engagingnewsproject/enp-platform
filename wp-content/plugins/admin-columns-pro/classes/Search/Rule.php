<?php

namespace ACP\Search;

final class Rule
{

    private string $name;

    private string $operator;

    private Value $value;

    public function __construct(string $name, string $operator, Value $value)
    {
        $this->name = $name;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_operator(): string
    {
        return $this->operator;
    }

    public function get_value(): Value
    {
        return $this->value;
    }

}