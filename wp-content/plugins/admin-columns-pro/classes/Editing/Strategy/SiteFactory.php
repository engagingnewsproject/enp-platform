<?php

declare(strict_types=1);

namespace ACP\Editing\Strategy;

use AC\TableScreen;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;
use ACP\TableScreen\NetworkSite;

class SiteFactory implements StrategyFactory
{

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof NetworkSite) {
            return null;
        }

        return new Site();
    }

}