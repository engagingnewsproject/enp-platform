<?php

declare(strict_types=1);

namespace ACA\BP\Editing\Strategy;

use AC;
use ACA\BP\TableScreen;
use ACP\Editing\Strategy;
use ACP\Editing\StrategyFactory;

class GroupFactory implements StrategyFactory
{

    public function create(AC\TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof TableScreen\Group) {
            return null;
        }

        return new Group();
    }

}