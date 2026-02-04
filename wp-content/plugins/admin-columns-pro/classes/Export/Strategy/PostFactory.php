<?php

declare(strict_types=1);

namespace ACP\Export\Strategy;

use AC\PostType;
use AC\TableScreen;
use AC\TableScreen\ListTable;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use ACP\Export\StrategyFactory;

class PostFactory implements StrategyFactory
{

    private ResponseFactory $response_factory;

    public function __construct(ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof PostType || ! $table_screen instanceof ListTable) {
            return null;
        }

        return new Post(
            new TableDataFactory($table_screen),
            $this->response_factory
        );
    }

}