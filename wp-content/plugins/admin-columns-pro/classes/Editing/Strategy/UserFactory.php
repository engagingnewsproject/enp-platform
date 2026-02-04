<?php

declare(strict_types=1);

namespace ACP\Editing\Strategy;

use AC;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;
use ACP\TableScreen;

class UserFactory implements StrategyFactory
{

    public function create(AC\TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof AC\TableScreen\User && ! $table_screen instanceof TableScreen\NetworkUser) {
            return null;
        }

        return new User();
    }

}