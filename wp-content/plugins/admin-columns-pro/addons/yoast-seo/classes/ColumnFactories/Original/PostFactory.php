<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactories\Original;

use AC\TableScreen;
use ACA\YoastSeo\ColumnFactory\Post;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class PostFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof TableScreen\Post) {
            return [];
        }

        return [
            'wpseo-focuskw'           => Post\Original\FocusKeywordFactory::class,
            'wpseo-metadesc'          => Post\Original\MetaDescFactory::class,
            'wpseo-score-readability' => Post\Original\ReadabilityFactory::class,
            'wpseo-score'             => Post\Original\ScoreFactory::class,
            'wpseo-title'             => Post\Original\MetaTitleFactory::class,
        ];
    }

}