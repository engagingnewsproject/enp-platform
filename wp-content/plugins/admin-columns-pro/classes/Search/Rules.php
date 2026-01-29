<?php

declare(strict_types=1);

namespace ACP\Search;

use AC\TypedArrayIterator;

final class Rules extends TypedArrayIterator
{

    public function __construct(array $array)
    {
        parent::__construct($array, Rule::class);
    }

    public function current(): Rule
    {
        return parent::current();
    }

}