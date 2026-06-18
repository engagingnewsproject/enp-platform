<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Robots;

final class NoIndex extends SingleDirective
{

    public function get_key(): string
    {
        return 'noindex';
    }

    public function get_label(): string
    {
        return __('No Index', 'rank-math');
    }

}