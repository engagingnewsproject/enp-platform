<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactory\Post\Original;

use AC\Setting\Config;
use ACA\RankMath\ColumnFactory\GroupTrait;
use ACP;
use ACP\Column\OriginalColumnFactory;

final class SeoTitle extends OriginalColumnFactory
{

    use GroupTrait;

    private const META_KEY = 'rank_math_title';

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Text(),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

}