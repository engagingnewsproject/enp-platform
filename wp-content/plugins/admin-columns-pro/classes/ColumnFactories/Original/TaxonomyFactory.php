<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC\TableScreen;
use ACP;
use ACP\ColumnFactory\Taxonomy\Original;

class TaxonomyFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof ACP\TableScreen\Taxonomy) {
            return [];
        }

        return [
            'description' => Original\Description::class,
            'name'        => Original\Name::class,
            'posts'       => Original\Posts::class,
            'slug'        => Original\Slug::class,
        ];
    }

}