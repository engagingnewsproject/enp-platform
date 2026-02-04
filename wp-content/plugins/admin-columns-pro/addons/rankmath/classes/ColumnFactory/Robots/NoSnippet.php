<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Robots;

final class NoSnippet extends SingleDirective
{

    public function get_key(): string
    {
        return 'nosnippet';
    }

    public function get_label(): string
    {
        return __('No Snippet', 'rank-math');
    }

}