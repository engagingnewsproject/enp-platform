<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison\Table;

use ACP\Search\Operators;

class Text extends TableStorage
{

    public function __construct(
        string $table,
        string $column,
        ?string $value_type = null
    ) {
        parent::__construct(new Operators([
            Operators::CONTAINS,
            Operators::NOT_CONTAINS,
            Operators::EQ,
            Operators::NEQ,
            Operators::BEGINS_WITH,
            Operators::ENDS_WITH,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ], false), $table, $column, $value_type);
    }

}