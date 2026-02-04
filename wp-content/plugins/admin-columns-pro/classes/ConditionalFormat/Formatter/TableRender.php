<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter;

use AC\Expression\AndSpecification;
use AC\Expression\ComparisonOperators;
use AC\Expression\ContainsSpecification;
use AC\Expression\DateComparisonSpecification;
use AC\Expression\DateOperators;
use AC\Expression\DateRelativeDaysSpecification;
use AC\Expression\DateRelativeDeductedSpecification;
use AC\Expression\EndsWithSpecification;
use AC\Expression\Exception\InvalidDateFormatException;
use AC\Expression\Exception\OperatorNotFoundException;
use AC\Expression\FloatComparisonSpecification;
use AC\Expression\IntegerComparisonSpecification;
use AC\Expression\RangeOperators;
use AC\Expression\Specification;
use AC\Expression\StartsWithSpecification;
use AC\Expression\StringComparisonSpecification;
use AC\Expression\StringOperators;
use AC\Formatter;
use AC\Type\Value;
use ACP;
use ACP\ConditionalFormat;
use ACP\ConditionalFormat\Entity\Rules;
use ACP\ConditionalFormat\Operators;
use RuntimeException;

class TableRender implements Formatter
{

    private ACP\Column $column;

    private Operators $operators;

    private Rules $rules;

    public function __construct(ACP\Column $column, Operators $operators, Rules $rules)
    {
        $this->column = $column;
        $this->operators = $operators;
        $this->rules = $rules;
    }

    public function format(Value $value)
    {
        if (Formatter\EmptyValue::DEFAULT === (string)$value) {
            return $value;
        }

        $config = $this->column->conditional_format();

        if ($config === null) {
            return $value;
        }

        foreach ($this->rules->get_rule_collection() as $rule) {
            if ($rule->get_column_name() !== (string)$this->column->get_id()) {
                continue;
            }

            if ( ! $this->operators->has_operator($rule->get_operator())) {
                return $value;
            }

            try {
                $formatter = $config->get_value_formatter();

                $specification = $this->get_specification(
                    $rule->get_operator(),
                    $formatter->get_type(),
                    $rule->has_fact() ? $rule->get_fact() : null
                );

                $formatted_value = $formatter->format(
                    (string)$value,
                    $value->get_id(),
                    $this->operators->get_group($rule->get_operator())
                );

                if ($specification->is_satisfied_by(strtolower($formatted_value))) {
                    return $value->with_value(
                        sprintf(
                            '<div class="%s">%s</div>',
                            esc_attr($rule->get_format()),
                            $value
                        )
                    );
                }
            } catch (RuntimeException $e) {
                return $value;
            }
        }

        return $value;
    }

    private function sanitize_fact($fact): string
    {
        return is_string($fact)
            ? strtolower($fact)
            : (string)$fact;
    }

    /**
     * @throws InvalidDateFormatException
     */
    private function get_specification(string $operator, string $type, $fact = null): Specification
    {
        switch ($operator) {
            case StringOperators::STARTS_WITH:
                return new StartsWithSpecification($this->sanitize_fact($fact));
            case StringOperators::ENDS_WITH:
                return new EndsWithSpecification($this->sanitize_fact($fact));
            case StringOperators::CONTAINS:
                return new ContainsSpecification($this->sanitize_fact($fact));
            case StringOperators::NOT_CONTAINS:
                $specification = new ContainsSpecification($this->sanitize_fact($fact));

                return $specification->not();
            case DateOperators::TODAY:
            case DateOperators::FUTURE:
            case DateOperators::PAST:
                return new DateRelativeDeductedSpecification($operator);
            case DateOperators::WITHIN_DAYS:
            case DateOperators::GT_DAYS_AGO:
            case DateOperators::LT_DAYS_AGO:
                return new DateRelativeDaysSpecification($operator, (int)$fact);
            case 'date_is':
                return new DateComparisonSpecification(ComparisonOperators::EQUAL, $fact);
            case 'date_is_after':
                return new DateComparisonSpecification(ComparisonOperators::GREATER_THAN, $fact);
            case 'date_is_before':
                return new DateComparisonSpecification(ComparisonOperators::LESS_THAN, $fact);
            case 'date_between':
                return new AndSpecification([
                    $this->get_specification(
                        'date_is_after',
                        $type,
                        $fact[0]
                    ),
                    $this->get_specification(
                        'date_is_before',
                        $type,
                        $fact[1]
                    ),
                ]);
            case ComparisonOperators::EQUAL:
            case ComparisonOperators::NOT_EQUAL:
            case ComparisonOperators::LESS_THAN:
            case ComparisonOperators::LESS_THAN_EQUAL:
            case ComparisonOperators::GREATER_THAN:
            case ComparisonOperators::GREATER_THAN_EQUAL:
                switch ($type) {
                    case ConditionalFormat\Formatter::INTEGER:
                        return new IntegerComparisonSpecification($operator, (int)$fact);
                    case ConditionalFormat\Formatter::FLOAT:
                        return new FloatComparisonSpecification($operator, (string)$fact);
                    case ConditionalFormat\Formatter::DATE:
                        if (false !== filter_var($fact, FILTER_SANITIZE_NUMBER_INT)) {
                            return new IntegerComparisonSpecification($operator, (int)$fact);
                        }

                        if (false !== filter_var($fact, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND)) {
                            return new FloatComparisonSpecification($operator, (string)$fact);
                        }
                }

                return new StringComparisonSpecification($operator, $this->sanitize_fact($fact));
            case RangeOperators::BETWEEN:
            case RangeOperators::NOT_BETWEEN:
                $specification = new AndSpecification([
                        $this->get_specification(
                            ComparisonOperators::GREATER_THAN_EQUAL,
                            $type,
                            $fact[0]
                        ),
                        $this->get_specification(
                            ComparisonOperators::LESS_THAN_EQUAL,
                            $type,
                            $fact[1]
                        ),
                    ]
                );

                if ($operator === RangeOperators::NOT_BETWEEN) {
                    $specification = $specification->not();
                }

                return $specification;
        }

        throw new OperatorNotFoundException($operator);
    }

}