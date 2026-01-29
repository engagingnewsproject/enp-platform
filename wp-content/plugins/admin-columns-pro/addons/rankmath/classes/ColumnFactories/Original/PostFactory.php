<?php

declare(strict_types=1);

namespace ACA\RankMath\ColumnFactories\Original;

use AC\TableScreen;
use ACA\RankMath\ColumnFactory\Post\Original;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class PostFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        return [
            'rank_math_title'       => Original\SeoTitle::class,
            'rank_math_description' => Original\SeoDescription::class,
            'rank_math_seo_details' => Original\SeoDetails::class,
        ];
    }

}