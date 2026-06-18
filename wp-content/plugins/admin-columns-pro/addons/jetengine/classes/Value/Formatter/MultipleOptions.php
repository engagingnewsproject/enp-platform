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

        // Do not use empty() — it treats '0' as empty, which is a valid option key
        if (null === $raw_value || '' === $raw_value) {
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