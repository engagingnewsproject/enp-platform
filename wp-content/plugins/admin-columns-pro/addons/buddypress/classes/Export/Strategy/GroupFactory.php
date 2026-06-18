<?php

declare(strict_types=1);

namespace ACA\BP\Export\Strategy;

use AC;
use ACA\BP\Export;
use ACA\BP\TableScreen;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use ACP\Export\StrategyFactory;

class GroupFactory implements StrategyFactory
{

    private ResponseFactory $response_factory;

    public function __construct(ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }

    public function create(AC\TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof TableScreen\Group) {
            return null;
        }

        return new Export\Strategy\Group(
            new TableDataFactory($table_screen),
            $this->response_factory
        );
    }

}