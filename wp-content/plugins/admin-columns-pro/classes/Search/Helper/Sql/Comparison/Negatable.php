<?php

namespace ACP\Search\Helper\Sql\Comparison;

interface Negatable
{

    public function is_negated(): bool;

}