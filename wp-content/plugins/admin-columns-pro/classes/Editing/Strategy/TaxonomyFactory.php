<?php

declare(strict_types=1);

namespace ACP\Editing\Strategy;

use AC;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;
use ACP\TableScreen;

class TaxonomyFactory implements StrategyFactory
{

    public function create(AC\TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof TableScreen\Taxonomy) {
            return null;
        }

        return new Taxonomy();
    }

}