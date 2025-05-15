<?php

namespace ACP\Search\Helper\MetaQuery\Comparison;

use ACP\Search\Helper\MetaQuery;
use ACP\Search\Value;

class BeginsWith extends MetaQuery\Comparison
{

    public function __construct(string $key, Value $value)
    {
        $value = new Value(
            '^' . $value->get_value(),
            $value->get_type()
        );

        parent::__construct($key, 'REGEXP', $value);
    }

}