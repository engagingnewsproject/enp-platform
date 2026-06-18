<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison;

use ACP;
use ACP\Search\Helper\MetaQuery\SerializedComparisonFactory;

class MultiSelect extends Select
{

    protected function get_meta_query(string $operator, ACP\Search\Value $value): array
    {
        $comparison = SerializedComparisonFactory::create(
            $this->get_meta_key(),
            $operator,
            $value
        );

        return $comparison();
    }

}