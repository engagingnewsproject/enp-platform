<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory;

trait GroupTrait
{

    protected function get_group(): ?string
    {
        return 'rank-math';
    }
}