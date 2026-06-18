<?php

declare(strict_types=1);

namespace ACP\Table\ManageValue;

use AC;
use AC\TableScreen\ManageValueService;
use AC\TableScreen\ManageValueServiceFactory;
use ACP\TableScreen;
use InvalidArgumentException;

class NetworkUserServiceFactory implements ManageValueServiceFactory
{

    public function can_create(AC\TableScreen $table_screen): bool
    {
        return $table_screen instanceof TableScreen\NetworkUser;
    }

    public function create(
        AC\TableScreen $table_screen,
        AC\Table\ManageValue\RenderFactory $factory,
        int $priority = 100
    ): ManageValueService {
        if ( ! $table_screen instanceof TableScreen\NetworkUser) {
            throw new InvalidArgumentException('Invalid table screen');
        }

        return new AC\TableScreen\ManageValue\User($factory, $priority);
    }

}