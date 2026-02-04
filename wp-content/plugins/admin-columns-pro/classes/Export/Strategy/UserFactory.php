<?php

declare(strict_types=1);

namespace ACP\Export\Strategy;

use AC;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use ACP\Export\StrategyFactory;
use ACP\TableScreen;

class UserFactory implements StrategyFactory
{

    private ResponseFactory $response_factory;

    public function __construct(ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }

    public function create(AC\TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof AC\TableScreen\User && ! $table_screen instanceof TableScreen\NetworkUser) {
            return null;
        }

        return new User(
            new TableDataFactory($table_screen),
            $this->response_factory
        );
    }

}