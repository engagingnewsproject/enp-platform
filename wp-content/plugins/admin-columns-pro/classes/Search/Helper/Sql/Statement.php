<?php

namespace ACP\Search\Helper\Sql;

use ACP\Search\Value;
use LogicException;

class Statement
{

    /**
     * @var Value[]
     */
    protected array $values = [];

    protected string $statement;

    protected string $value_type;

    public function __construct(string $statement)
    {
        $this->statement = $statement;
    }

    public function bind_value(Value $value): self
    {
        $this->values[] = $value;

        return $this;
    }

    /**
     * Prepare string for safe usage
     */
    public function prepare(): string
    {
        global $wpdb;

        if (substr_count($this->statement, '?') !== count($this->values)) {
            throw new LogicException('Amount of parameters and variables must be the same.');
        }

        $statement = $this->statement;
        $values = [];

        foreach ($this->values as $value) {
            $type = $value->get_type() === Value::INT
                ? '%d'
                : '%s';

            $statement = substr_replace(
                $statement,
                $type,
                strpos($statement, '?'),
                1
            );

            $values[] = $value->get_value();
        }

        return $wpdb->prepare($statement, $values);
    }

}