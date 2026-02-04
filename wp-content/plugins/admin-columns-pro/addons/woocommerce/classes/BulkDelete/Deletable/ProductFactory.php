<?php

declare(strict_types=1);

namespace ACA\WC\BulkDelete\Deletable;

use AC\PostType;
use AC\TableScreen;
use ACP\Editing\BulkDelete\Deletable;
use ACP\Editing\BulkDelete\StrategyFactory;
use WP_Post_Type;

class ProductFactory implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Deletable
    {
        if ( ! $table_screen instanceof PostType || ! $table_screen->get_post_type()->equals('product')) {
            return null;
        }

        $post_type = get_post_type_object('product');

        if ( ! $post_type instanceof WP_Post_Type) {
            return null;
        }

        return new Product($post_type);
    }

}