<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Strategy;

use AC\TableScreen;
use ACA\GravityForms;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use ACP\Export\StrategyFactory;

class EntryFactory implements StrategyFactory
{

    private ResponseFactory $response_factory;

    public function __construct(ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }

    public function create(TableScreen $table_screen): ?Strategy
    {
        if ( ! $table_screen instanceof GravityForms\TableScreen\Entry) {
            return null;
        }

        return new Entry(
            $table_screen,
            new TableDataFactory($table_screen),
            $this->response_factory
        );
    }

}