<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison\Table;

use ACP\Search\Labels;
use ACP\Search\Operators;
use ACP\Search\Value;

class Timestamp extends TableStorage
{

    public function __construct(string $table, string $column)
    {
        $operators = new Operators([
            Operators::EQ,
            Operators::GT,
            Operators::LT,
            Operators::GTE,
            Operators::LTE,
            Operators::BETWEEN,
            Operators::TODAY,
            Operators::PAST,
            Operators::FUTURE,
            Operators::BETWEEN,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
            Operators::LT_DAYS_AGO,
            Operators::GT_DAYS_AGO,
            Operators::EQ_MONTH,
            Operators::EQ_YEAR,
        ], false);

        parent::__construct($operators, $table, $column, Value::DATE, new Labels\Date());
    }

    protected function get_subquery(string $operator, Value $value): string
    {
        $time = is_array($value->get_value())
            ? array_map([$this, 'to_time'], $value->get_value())
            : $this->to_time((string)$value->get_value());

        switch ($operator) {
            case Operators::EQ:
                $operator = Operators::BETWEEN;
                $value = new Value(
                    [
                        $time,
                        $time + DAY_IN_SECONDS - 1,
                    ],
                    Value::INT
                );

                break;
            default:
                $value = new Value($time, Value::INT);
        }

        return parent::get_subquery($operator, $value);
    }

    private function to_time($value): int
    {
        return (int)strtotime((string)$value);
    }
}