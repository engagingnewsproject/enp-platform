<?php

declare(strict_types=1);

namespace ACA\ACF\FieldGroup;

use AC;
use ACA\ACF\FieldGroup;
use ACA\WC;
use ACP;

final class QueryFactory
{

    public function create(AC\TableScreen $table_screen): ?Query
    {
        switch (true) {
            case $table_screen instanceof AC\TableScreen\Media:
                return new FieldGroup\Location\Media();
            case $table_screen instanceof AC\TableScreen\Post:
                return new FieldGroup\Location\Post((string)$table_screen->get_post_type());
            case $table_screen instanceof AC\TableScreen\User:
                return new FieldGroup\Location\User();
            case $table_screen instanceof ACP\TableScreen\Taxonomy:
                return new FieldGroup\Location\Taxonomy();
            case $table_screen instanceof AC\TableScreen\Comment:
                return new FieldGroup\Location\Comment();
            case $table_screen instanceof WC\TableScreen\Order:
                return new FieldGroup\Location\Post('shop_order');
        }

        return null;
    }

}