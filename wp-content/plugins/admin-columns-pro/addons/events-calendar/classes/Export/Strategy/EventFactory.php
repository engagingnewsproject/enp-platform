<?php

declare(strict_types=1);

namespace ACA\EC\Export\Strategy;

use AC;
use AC\TableScreen;
use ACP;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;

class EventFactory implements ACP\Export\StrategyFactory
{

    private ResponseFactory $response_factory;

    public function __construct(ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof AC\PostType || ! $table_screen->get_post_type()->equals('tribe_events')) {
            return null;
        }

        if ( ! $table_screen instanceof AC\TableScreen\ListTable) {
            return null;
        }

        return new Event(
            new TableDataFactory($table_screen),
            $this->response_factory
        );
    }

}