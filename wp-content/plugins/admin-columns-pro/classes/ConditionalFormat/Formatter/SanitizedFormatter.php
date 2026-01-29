<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter;

use AC\Expression\ComparisonOperators;
use AC\Expression\DateOperators;
use AC\Expression\StringOperators;
use ACP\ConditionalFormat\Formatter;
use InvalidArgumentException;

final class SanitizedFormatter implements Formatter
{

    private Formatter $formatter;

    private array $ignored_operator_groups;

    public function __construct(Formatter $formatter, array $ignored_operator_groups = [])
    {
        $this->formatter = $formatter;
        $this->ignored_operator_groups = $ignored_operator_groups;

        $this->validate();
    }

    public static function from_ignore_strings(Formatter $formatter): self
    {
        return new self($formatter, [
            StringOperators::class,
        ]);
    }

    public function format(string $value, $id, string $operator_group): string
    {
        if ( ! in_array($operator_group, $this->ignored_operator_groups, true)) {
            $value = $this->sanitize($value);
        }

        return $this->formatter->format($value, $id, $operator_group);
    }

    public function get_type(): string
    {
        return $this->formatter->get_type();
    }

    private function validate(): void
    {
        $valid_operator_groups = [
            StringOperators::class,
            ComparisonOperators::class,
            DateOperators::class,
        ];

        foreach ($this->ignored_operator_groups as $ignored_operator_group) {
            if ( ! in_array($ignored_operator_group, $valid_operator_groups, true)) {
                throw new InvalidArgumentException(
                    sprintf('%s is not a valid operator group.', $ignored_operator_group)
                );
            }
        }
    }

    protected function sanitize(string $value): string
    {
        switch ($this->formatter->get_type()) {
            case Formatter::INTEGER:
                $value = filter_var(
                    $value,
                    FILTER_SANITIZE_NUMBER_INT
                );

                break;
            case Formatter::FLOAT:
                $value = filter_var(
                    $value,
                    FILTER_SANITIZE_NUMBER_FLOAT,
                    FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_SCIENTIFIC
                );

                break;
        }

        return $value;
    }

}