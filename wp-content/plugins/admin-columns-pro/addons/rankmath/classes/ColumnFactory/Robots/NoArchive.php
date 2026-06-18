<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Robots;

final class NoArchive extends SingleDirective
{

    protected function get_key(): string
    {
        return 'noarchive';
    }

    public function get_label(): string
    {
        return __('No Archive', 'rank-math');
    }

}