<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\Media;

use ACP\ColumnFactory\Post\Content;

class Description extends Content
{

    public function get_label(): string
    {
        return __('Description', 'codepress-admin-columns');
    }
}