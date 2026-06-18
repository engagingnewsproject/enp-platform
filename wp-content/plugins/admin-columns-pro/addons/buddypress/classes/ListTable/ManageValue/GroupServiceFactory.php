<?php

declare(strict_types=1);

namespace ACA\BP\ListTable\ManageValue;

use AC\Table\ManageValue\RenderFactory;
use AC\TableScreen;
use AC\TableScreen\ManageValueService;
use AC\TableScreen\ManageValueServiceFactory;
use ACA\BP;

class GroupServiceFactory implements ManageValueServiceFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof BP\TableScreen\Group;
    }

    public function create(
        TableScreen $table_screen,
        RenderFactory $factory,
        int $priority = 100
    ): ManageValueService {
        return new Group($factory);
    }

}