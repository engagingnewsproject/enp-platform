<?php

declare(strict_types=1);

namespace ACA\ACF\FieldGroup;

use AC;
use AC\Acf\FieldGroup\Location;
use ACA\WC;
use ACP;

final class QueryFactory extends AC\Acf\FieldGroup\QueryFactory
{

    public function create(AC\TableScreen $table_screen): ?AC\Acf\FieldGroup\Query
    {
        if ($table_screen instanceof ACP\TableScreen\Taxonomy) {
            return new Location\Taxonomy();
        }

        if ($table_screen instanceof WC\TableScreen\Order) {
            return new Location\Post('shop_order');
        }

        return parent::create($table_screen);
    }

}