<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Robots;

final class NoFollow extends SingleDirective
{

    public function get_key(): string
    {
        return 'nofollow';
    }

    public function get_label(): string
    {
        return __('No Follow', 'rank-math');
    }

}