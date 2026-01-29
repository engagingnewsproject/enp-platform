<?php

declare(strict_types=1);

namespace ACA\RankMath\Search\Comparison;

use ACP\Search\Comparison;
use ACP\Search\Operators;
use ACP\Search\Value;

class Robots extends Comparison\Meta
{

    private string $key;

    public function __construct(
        string $key
    ) {
        parent::__construct(new Operators([
            Operators::IS_EMPTY,
            Operators::NOT_IS_EMPTY,
        ], false), 'rank_math_robots');
        $this->key = $key;
    }

    protected function get_meta_query(string $operator, Value $value): array
    {
        return [
            'key'     => $this->get_meta_key(),
            'value'   => serialize($this->key),
            'compare' => $operator === Operators::IS_EMPTY ? 'NOT LIKE' : 'LIKE',
        ];
    }

}