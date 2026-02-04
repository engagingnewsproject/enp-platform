<?php

declare(strict_types=1);

namespace ACA\Types\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class CheckboxLabels implements Formatter
{

    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function format(Value $value)
    {
        $raw = $value->get_value();

        if (empty($raw)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        // Checkbox keys
        $values = [];

        foreach ($raw as $key) {
            $key = (string)$key[0];
            $values[] = array_key_exists($key, $this->options)
                ? $this->options[$key]
                : $key;
        }

        return $value->with_value($values);
    }
}