<?php

declare(strict_types=1);

namespace ACP\Sorting\Controller;

use AC\ListScreen;
use ACP\Sorting\ModelFactory;

class ManageQueryHandlerFactory
{

    private ModelFactory $factory;

    public function __construct(ModelFactory $factory)
    {
        $this->factory = $factory;
    }

    public function create(ListScreen $listScreen): ManageQueryHandler
    {
        return new ManageQueryHandler($listScreen, $this->factory);
    }

}