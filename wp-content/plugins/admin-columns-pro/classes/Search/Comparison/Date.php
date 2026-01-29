<?php

namespace ACP\Search\Comparison;

use ACP\Query\Bindings;
use ACP\Search\Comparison;
use ACP\Search\Helper\DateValueFactory;
use ACP\Search\Helper\Sql;
use ACP\Search\Helper\Sql\ComparisonFactory;
use ACP\Search\Labels;
use ACP\Search\Operators;
use ACP\Search\Value;
use DateTime;
use RuntimeException;

abstract class Date extends Comparison
{

    /**
     * DB column for SQL clause
     */
    abstract protected function get_column(): string;

    public function __construct(Operators $operators)
    {
        parent::__construct($operators, Value::DATE, new Labels\Date());
    }

    private function create_date(string $date): DateTime
    {
        $date = DateTime::createFromFormat('Y-m-d', $date);

        if (false === $date) {
            throw new RuntimeException('Invalid date format.');
        }

        return $date;
    }

    protected function get_sql_comparison(string $operator, Value $value): Sql\Comparison
    {
        $value_factory = new DateValueFactory($value->get_type());

        switch ($operator) {
            case Operators::GT :
            case Operators::LTE :
                return $this->create_comparison(
                    $operator,
                    $value_factory->create_end_of_the_day($this->create_date($value->get_value()))
                );
            case Operators::LT :
            case Operators::GTE :
                return $this->create_comparison(
                    $operator,
                    $value_factory->create_start_of_the_day($this->create_date($value->get_value()))
                );
            case Operators::BETWEEN :
                $dates = $value->get_value();

                if ( ! is_array($dates)) {
                    throw new RuntimeException('Invalid range.');
                }

                return $this->create_comparison(
                    $operator,
                    $value_factory->create_range_full_day(
                        $this->create_date($dates[0]),
                        $this->create_date($dates[1]),
                    )
                );
            case Operators::EQ :
                return $this->create_comparison(
                    Operators::BETWEEN,
                    $value_factory->create_range_single_day(
                        $this->create_date($value->get_value())
                    )
                );
            default :
                return $this->create_comparison($operator, $value);
        }
    }

    private function create_comparison(string $operator, Value $value): Sql\Comparison
    {
        return ComparisonFactory::create(
            $this->get_column(),
            $operator,
            $value
        );
    }

    protected function create_query_bindings(string $operator, Value $value): Bindings
    {
        $bindings = new Bindings();

        $bindings->where(
            $this->get_sql_comparison($operator, $value)->prepare()
        );

        return $bindings;
    }

}