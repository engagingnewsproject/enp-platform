<?php

declare(strict_types=1);

namespace ACP\Editing\BulkDelete\Deletable;

use AC;
use AC\TableScreen;
use ACP\Editing\BulkDelete\Deletable;
use ACP\Editing\BulkDelete\StrategyFactory;

class TaxonomyFactory implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Deletable
    {
        if ( ! $table_screen instanceof AC\Taxonomy) {
            return null;
        }

        return new Taxonomy(
            $table_screen->get_taxonomy()
        );
    }

}