<?php

namespace ACP\Search\Comparison\Post\Parent\Meta;

use ACP\Search\Comparison\Post\Parent\Meta;
use ACP\Search\Operators;

class Text extends Meta
{

    public function __construct(string $meta_key)
    {
        parent::__construct(
            $meta_key,
            new Operators([
                Operators::CONTAINS,
                Operators::NOT_CONTAINS,
                Operators::EQ,
                Operators::BEGINS_WITH,
                Operators::ENDS_WITH,
            ], false)
        );
    }

}