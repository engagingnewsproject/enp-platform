<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactories\Original;

use AC\TableScreen;
use ACA\YoastSeo\ColumnFactory\DisableExportOriginalColumnFactory;
use ACP;
use ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory;

class TaxonomyFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof ACP\TableScreen\Taxonomy) {
            return [];
        }

        return [
            'wpseo-score-readability' => DisableExportOriginalColumnFactory::class,
            'wpseo-score'             => DisableExportOriginalColumnFactory::class,
        ];
    }

}