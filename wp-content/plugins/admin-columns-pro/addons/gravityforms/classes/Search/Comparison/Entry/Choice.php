<?php

declare(strict_types=1);

namespace ACA\GravityForms\Search\Comparison\Entry;

use AC\Helper\Select\Options;
use ACA\GravityForms\Search;
use ACP;
use ACP\Search\Operators;
use ACP\Search\Value;

class Choice extends Search\Comparison\Entry implements ACP\Search\Comparison\Values
{

    private $choices;

    public function __construct(string $field, array $choices)
    {
        $operators = new Operators([
            Operators::EQ,
            Operators::NEQ,
        ]);

        parent::__construct($field, $operators, Value::STRING);

        $this->choices = $choices;
    }

    public function get_values(): Options
    {
        return Options::create_from_array($this->choices);
    }

}