<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Formatter;

use AC\Formatter\Aggregate;
use AC\Type\Value;

class FormatCollectionFormatter extends BaseFormatter
{

    private Aggregate $formatter;

    public function __construct(Aggregate $formatter, string $type = self::STRING)
    {
        parent::__construct($type);

        $this->formatter = $formatter;
    }

    public static function create(array $formatters): self
    {
        return new self(Aggregate::from_array($formatters));
    }

    public function format(string $value, $id, string $operator_group): string
    {
        $value_object = $this->formatter->format(
            new Value($id, $value)
        );

        if ( ! $value_object instanceof Value) {
            return '';
        }

        return parent::format(
            (string)$value_object,
            $value_object->get_id(),
            $operator_group
        );
    }

}