<?php

declare(strict_types=1);

namespace ACA\WC\Search\OrderMeta;

use ACP\Search\Comparison\Meta;
use ACP\Search\Labels;
use ACP\Search\Operators;
use ACP\Search\Value;

class AcfDate extends Meta
{

    public function __construct(string $meta_key)
    {
        $operators = new Operators([
            Operators::EQ,
            Operators::GT,
            Operators::LT,
            Operators::BETWEEN,
            Operators::TODAY,
            Operators::PAST,
            Operators::FUTURE,
            Operators::WITHIN_DAYS,
            Operators::LT_DAYS_AGO,
            Operators::GT_DAYS_AGO,
            Operators::EQ_YEAR,
            Operators::EQ_MONTH,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]);

        parent::__construct($operators, $meta_key, Value::DATE, new Labels\Date());
    }

    private function map_value(Value $value, string $operator): Value
    {
        switch ($operator) {
            case Operators::EQ_MONTH:
            case Operators::EQ_YEAR:
            case Operators::GT_DAYS_AGO:
            case Operators::LT_DAYS_AGO:
            case Operators::WITHIN_DAYS:
            case Operators::TODAY:
                return $value;
            default:
                return new Value(
                    $this->format_date($value->get_value()),
                    Value::INT
                );
        }
    }

    protected function get_meta_query(string $operator, Value $value): array
    {
        $value = $this->map_value($value, $operator);

        switch ($operator) {
            case Operators::EQ_YEAR:
            case Operators::EQ_MONTH:
                return [
                    'key'     => $this->get_meta_key(),
                    'compare' => 'LIKE',
                    'value'   => str_replace('-', '', $value->get_value()),
                ];
            case Operators::FUTURE:
            case Operators::PAST:
                return [
                    'key'     => $this->get_meta_key(),
                    'compare' => Operators::FUTURE === $operator ? '>' : '<',
                    'type'    => 'NUMERIC',
                    'value'   => date('Ymd'),
                ];
            default:
                return parent::get_meta_query($operator, $value);
        }
    }

    /**
     * @param array|string $value
     *
     * @return array|string
     */
    private function format_date($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->format_date($item);
            }
        } else {
            $value = date('Ymd', strtotime($value));
        }

        return $value;
    }

}