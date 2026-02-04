<?php

declare(strict_types=1);

namespace ACA\WC\Editing\Strategy;

use AC\PostType;
use AC\TableScreen;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;

class ProductFactory implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof PostType || ! $table_screen->get_post_type()->equals('product')) {
            return null;
        }

        return new Product(get_post_type_object('product'));
    }

}