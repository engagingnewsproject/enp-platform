<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories\Original;

use AC\TableScreen;
use ACA\WC\ColumnFactory;
use ACP;

class ProductCategoryFactory extends ACP\ColumnFactories\Original\OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof ACP\TableScreen\Taxonomy ||
             ! $table_screen->get_taxonomy()->equals('product_cat')) {
            return [];
        }

        return [
            'thumb' => ColumnFactory\ProductCategory\Original\ImageFactory::class,
        ];
    }

}