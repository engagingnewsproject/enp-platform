<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Robots;

final class Index extends SingleDirective
{

    protected function get_key(): string
    {
        return 'index';
    }

    public function get_label(): string
    {
        return __('Index', 'rank-math');
    }

}