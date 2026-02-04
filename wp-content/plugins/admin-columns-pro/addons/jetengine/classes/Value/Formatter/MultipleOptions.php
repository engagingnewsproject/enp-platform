<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class MultipleOptions implements Formatter
{

    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function format(Value $value): ValueCollection
    {
        $raw_value = $value->get_value();

        if (empty($raw_value)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $values = new ValueCollection($value->get_id());
        foreach ($raw_value as $key) {
            $values->add(
                new Value($value->get_id(), $this->options[$key] ?? $key)
            );
        }

        return $values;
    }

}