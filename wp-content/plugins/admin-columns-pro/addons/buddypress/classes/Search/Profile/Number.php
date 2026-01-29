<?php

declare(strict_types=1);

namespace ACA\BP\Search\Profile;

use ACA\BP\Helper\Select;
use ACA\BP\Search;
use ACP\Search\Operators;
use ACP\Search\Value;

class Number extends Search\Profile
{

    public function __construct(int $field)
    {
        $operators = new Operators([
            Operators::EQ,
            Operators::BETWEEN,
            Operators::LT,
            Operators::LTE,
            Operators::GT,
            Operators::GTE,
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ]);

        parent::__construct($operators, $field, Value::INT);
    }

}