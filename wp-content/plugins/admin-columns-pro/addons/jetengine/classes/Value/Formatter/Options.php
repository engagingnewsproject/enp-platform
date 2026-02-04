<?php

declare(strict_types=1);

namespace ACA\JetEngine\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Options implements Formatter
{

    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function format(Value $value)
    {
        $raw_value = $value->get_value();

        if (empty($raw_value)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($this->options[$raw_value] ?? $raw_value);
    }

}