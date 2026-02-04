<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison\Table;

use AC\Helper\Select\Options;
use ACP;

class Select extends TableStorage implements ACP\Search\Comparison\Values
{

    protected array $choices;

    public function __construct(string $table, string $column, array $choices)
    {
        $operators = new ACP\Search\Operators([
            ACP\Search\Operators::EQ,
            ACP\Search\Operators::NEQ,
            ACP\Search\Operators::IS_EMPTY,
            ACP\Search\Operators::NOT_IS_EMPTY,
        ]);

        parent::__construct($operators, $table, $column);

        $this->choices = $choices;
    }

    public function get_values(): Options
    {
        $options = empty($this->choices) ? [] : $this->choices;

        return Options::create_from_array($options);
    }

}