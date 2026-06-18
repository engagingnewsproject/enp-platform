<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class TextList implements AC\Formatter
{

    private array $labels;

    public function __construct(array $labels)
    {
        $this->labels = array_values($labels);
    }

    public function format(Value $value)
    {
        $list = $value->get_value();

        if ( ! is_array($list)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $values = [];

        foreach ($list as $i => $list_value) {
            if ( ! array_key_exists($i, $this->labels)) {
                break;
            }

            $values[] = sprintf('<strong>%s:</strong> %s', $this->labels[$i], $list_value);
        }

        return $value->with_value(implode('<br>', $values));
    }

}