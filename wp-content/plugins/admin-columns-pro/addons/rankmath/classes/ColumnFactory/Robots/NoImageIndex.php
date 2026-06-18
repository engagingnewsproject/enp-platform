<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Robots;

final class NoImageIndex extends SingleDirective
{

    public function get_key(): string
    {
        return 'noimageindex';
    }

    public function get_label(): string
    {
        return __('No Image Index', 'rank-math');
    }

}